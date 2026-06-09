<?php

namespace Drupal\locale;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\locale\File\LocaleFile;

/**
 * Provides the locale source services.
 */
class LocaleSource {

  /**
   * The hash algorithm used to calculate the hash of the local file.
   */
  public const string LOCAL_FILE_HASH_ALGO = 'xxh128';

  /**
   * They key for the last checked information in the key value store.
   */
  protected const string LAST_CHECKED = 'last-checked';

  public function __construct(
    protected readonly LocaleProjectRepository $localeProjectRepository,
    protected readonly FileSystemInterface $fileSystem,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly CurrentImportStorage $currentImportStorage,
    protected readonly KeyValueFactoryInterface $keyValueFactory,
    protected readonly TimeInterface $time,
  ) {}

  /**
   * Loads cached translation sources containing current translation status.
   *
   * @param array|null $projects
   *   Array of project names. Defaults to all translatable projects.
   * @param array|null $langcodes
   *   Array of language codes. Defaults to all translatable languages.
   *
   * @return \Drupal\locale\LocaleTranslationSource[][]
   *   Array of source objects. Keyed with <project name>:<language code>.
   *
   * @see sourceBuild()
   */
  public function loadSources(?array $projects = NULL, ?array $langcodes = NULL): array {
    $projects = $projects ?: array_keys($this->localeProjectRepository->getAll());
    $langcodes = $langcodes ?: array_keys(locale_translatable_language_list());

    // If there are no translatable languages, return early.
    if (!$langcodes) {
      return [];
    }

    // Load source data from locale_translation_status key value store.
    $sources = $this->keyValueFactory->get('locale.translation_status')->getMultiple($projects);

    // Build sources that are missing.
    foreach ($projects as $project_name) {
      foreach ($langcodes as $langcode) {
        if (!isset($sources[$project_name][$langcode])) {
          $project = $this->localeProjectRepository->getMultiple([$project_name])[$project_name];
          $sources[$project_name][$langcode] = $this->sourceBuild($project, $langcode);
        }
      }
    }
    return $sources;
  }

  /**
   * Loads a single translation source containing current translation status.
   *
   * @param string $project_name
   *   The project name.
   * @param string $langcode
   *   The language code.
   *
   * @return \Drupal\locale\LocaleTranslationSource
   *   The source object.
   */
  public function loadSource(string $project_name, string $langcode): LocaleTranslationSource {
    return $this->loadSources([$project_name], [$langcode])[$project_name][$langcode];
  }

  /**
   * Saves the status of translation sources in static cache.
   *
   * @param string $project
   *   Machine readable project name.
   * @param string $langcode
   *   Language code.
   * @param string $type
   *   Type of data to be stored.
   * @param \Drupal\locale\File\LocaleFile|\Drupal\locale\CurrentImport $data
   *   Locale file or current import object.
   */
  public function saveSource(string $project, string $langcode, string $type, LocaleFile|CurrentImport $data): void {
    // Load the translation status.
    $project_sources = $this->loadSources([$project])[$project];

    // Merge the new status data with the existing status.
    $request_time = $this->time->getRequestTime();
    switch ($type) {
      case LOCALE_TRANSLATION_REMOTE:
        // Add the source data to the status array.
        $project_sources[$langcode]->files[$type] = $data;

        // Check if this translation is the most recent one. Set timestamp and
        // data type of the most recent translation source.
        if (isset($data->timestamp) && $data->timestamp) {
          if ($data->timestamp > $project_sources[$langcode]->timestamp) {
            $project_sources[$langcode]->timestamp = $data->timestamp;
            $project_sources[$langcode]->last_checked = $request_time;
            $project_sources[$langcode]->type = $type;
          }
        }
        break;

      case LOCALE_TRANSLATION_LOCAL:
        // Add the source data to the status array.
        $project_sources[$langcode]->files[$type] = $data;

        // Determine if the translation source has changed by comparing by
        // content hash (mtime is unreliable).
        $current_hash = $project_sources[$langcode]->hash ?? '';
        if (!empty($current_hash)) {
          if ($data->hash !== $current_hash) {
            $project_sources[$langcode]->timestamp = $data->timestamp;
            $project_sources[$langcode]->last_checked = $request_time;
            $project_sources[$langcode]->type = $type;
            $project_sources[$langcode]->hash = $data->hash;
          }
        }
        // For legacy rows (pre-hash column being added), fall back to
        // timestamp comparison. This may trigger a one-time re-import, after
        // which a hash is stored and used for all future comparisons.
        elseif (isset($data->timestamp) && $data->timestamp) {
          if ($data->timestamp > $project_sources[$langcode]->timestamp) {
            $project_sources[$langcode]->timestamp = $data->timestamp;
            $project_sources[$langcode]->last_checked = $request_time;
            $project_sources[$langcode]->type = $type;
            $project_sources[$langcode]->hash = $data->hash;
          }
        }
        break;

      case LOCALE_TRANSLATION_CURRENT:
        $data->last_checked = $request_time;
        $project_sources[$langcode]->timestamp = $data->timestamp;
        $project_sources[$langcode]->hash = $data->hash;
        $project_sources[$langcode]->last_checked = $data->last_checked;
        $project_sources[$langcode]->type = $type;
        $this->currentImportStorage->save(CurrentImport::createFromSource($data));
        break;
    }

    $this->keyValueFactory->get('locale.translation_status')->set($project, $project_sources);
  }

  /**
   * Delete project entries from the status cache.
   *
   * @param array $projects
   *   Project name(s) to be deleted from the cache.
   */
  public function deleteSources(array $projects): void {
    $this->keyValueFactory->get('locale.translation_status')->deleteMultiple($projects);
  }

  /**
   * Deletes sources for a given language code.
   *
   * @param string $langcode
   *   The language code to delete.
   */
  public function deleteSourcesByLanguage(string $langcode): void {
    $sources = $this->loadSources();
    foreach ($sources as $project_name => $project_sources) {
      if (isset($project_sources[$langcode])) {
        unset($project_sources[$langcode]);
        $this->keyValueFactory->get('locale.translation_status')->set($project_name, $project_sources);
      }
    }
  }

  /**
   * Clear the translation status cache.
   */
  public function clearSources(): void {
    $this->keyValueFactory->get('locale.translation_status')->deleteAll();
  }

  /**
   * Updates the last checked timestamp.
   */
  public function updateLastChecked(): void {
    $this->keyValueFactory->get('locale.translation_status')->set(static::LAST_CHECKED, $this->time->getRequestTime());
  }

  /**
   * Returns the last checked timestamp.
   *
   * @return int|null
   *   The last checked timestamp or NULL if not set.
   */
  public function getLastChecked(): ?int {
    return $this->keyValueFactory->get('locale.translation_status')->get(static::LAST_CHECKED);
  }

  /**
   * Build translation sources.
   *
   * @param array $projects
   *   Array of project names. Defaults to all translatable projects.
   * @param array $langcodes
   *   Array of language codes. Defaults to all translatable languages.
   *
   * @return array
   *   Array of source objects. Keyed by project name and language code.
   *
   * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
   *    \Drupal::service(LocaleSource::class)->buildSource($project, $langcodes)
   *    instead.
   *
   * @see https://www.drupal.org/node/3569330
   */
  public function buildSources(array $projects, array $langcodes = []): array {
    $sources = [];
    $projects = $this->localeProjectRepository->getMultiple($projects);
    $langcodes = $langcodes ?: array_keys(locale_translatable_language_list());

    foreach ($projects as $project) {
      foreach ($langcodes as $langcode) {
        $source = $this->sourceBuild($project, $langcode);
        $sources[$source->name][$source->langcode] = $source;
      }
    }
    return $sources;
  }

  /**
   * Checks whether a po file exists in the local filesystem.
   *
   * It will search in the directory set in the translation source. Which
   * defaults to the "translations://" stream wrapper path. The directory
   * may contain any valid stream wrapper.
   *
   * The "local" files property of the source object contains the definition of
   * a po file we are looking for. The file name defaults to
   * %project-%version.%language.po. Per project this value can be overridden
   * using the server_pattern directive in the module's .info.yml file or by
   * using hook_locale_translation_projects_alter().
   *
   * @param object $source
   *   Translation source object.
   *
   * @return object
   *   Source file object of the po file, updated with:
   *   - "uri": File name and path.
   *   - "timestamp": Last updated time of the po file.
   *   FALSE if the file is not found.
   *
   * @see sourceBuild()
   */
  public function sourceCheckFile($source) {
    if (isset($source->files[LOCALE_TRANSLATION_LOCAL])) {
      $source_file = $source->files[LOCALE_TRANSLATION_LOCAL];
      if (isset($source_file->uri) && file_exists($source_file->uri)) {
        $source_file->timestamp = filemtime($source_file->uri);
        $source_file->hash = hash_file(self::LOCAL_FILE_HASH_ALGO, $source_file->uri);
        return $source_file;
      }
    }
    return FALSE;
  }

  /**
   * Builds abstract translation source.
   *
   * @param \Drupal\locale\LocaleTranslatableProject $project
   *   Project object.
   * @param string $langcode
   *   Language code.
   * @param string $filename
   *   (optional) File name of translation file. May contain placeholders.
   *   Defaults to the default translation filename from the settings.
   *
   * @return \Drupal\locale\LocaleTranslationSource
   *   The locale translation source object.
   */
  public function sourceBuild(LocaleTranslatableProject $project, string $langcode, ?string $filename = NULL): LocaleTranslationSource {
    // Create a source object with data of the project object.
    $source = LocaleTranslationSource::fromProject($project, $langcode);

    $filename = $filename ?: $this->configFactory->get('locale.settings')->get('translation.default_filename');

    // If the server_pattern contains a remote file path we will check for a
    // remote file. The local version of this file will only be checked if a
    // translations directory has been defined. If the server_pattern is a local
    // file path we will only check for a file in the local file system.
    $files = [];
    if ($this->fileIsRemote($source->server_pattern)) {
      $remote_filename = $this->buildServerPattern($source, basename($source->server_pattern));
      $remote_uri = $this->buildServerPattern($source, $source->server_pattern);
      $remote_file = new LocaleFile($remote_filename, $remote_uri, '', NULL, $langcode, $project->name, $project->version);
      $remote_file->type = LOCALE_TRANSLATION_REMOTE;
      $files[LOCALE_TRANSLATION_REMOTE] = $remote_file;

      $local_filename = $this->buildServerPattern($source, $filename);
      $local_uri = 'translations://' . $local_filename;
      $local_file = new LocaleFile($local_filename, $local_uri, '', NULL, $langcode, $project->name, $project->version);
      $local_file->type = LOCALE_TRANSLATION_LOCAL;
      $local_file->directory = 'translations://';
      $files[LOCALE_TRANSLATION_LOCAL] = $local_file;
    }
    else {
      $local_directory = $this->buildServerPattern($source, $this->fileSystem->dirname($source->server_pattern));
      $local_filename = $this->buildServerPattern($source, basename($source->server_pattern));
      $local_uri = $local_directory . '/' . $local_filename;
      $local_file = new LocaleFile($local_filename, $local_uri, '', NULL, $langcode, $project->name, $project->version);
      $local_file->type = LOCALE_TRANSLATION_LOCAL;
      $local_file->directory = $local_directory;
      $files[LOCALE_TRANSLATION_LOCAL] = $local_file;
    }
    $source->files = $files;

    // If this project+language is already translated, we add its status and
    // update the current translation timestamp and last_updated time. If the
    // project+language is not translated before, create a new record.
    $current_import_state = $this->currentImportStorage->get($project->name, $langcode);
    if ($current_import_state instanceof CurrentImport && $current_import_state->timestamp) {
      $source->type = LOCALE_TRANSLATION_CURRENT;
      $source->timestamp = $current_import_state->timestamp;
      $source->hash = $current_import_state->hash;
      $source->last_checked = $current_import_state->last_checked ?? NULL;
    }
    elseif (!$current_import_state) {
      $this->currentImportStorage->save(CurrentImport::createFromSource($source));
    }

    return $source;
  }

  /**
   * Build path to translation source, out of a server path replacement pattern.
   *
   * @param object $project
   *   Project object containing data to be inserted in the template.
   * @param string $template
   *   String containing placeholders. Available placeholders:
   *   - "%project": Project name.
   *   - "%version": Project version.
   *   - "%core": Project core version.
   *   - "%language": Language code.
   *
   * @return string
   *   String with replaced placeholders.
   */
  public function buildServerPattern($project, $template): string {
    $variables = [
      '%project' => $project->name,
      '%version' => $project->version,
      '%core' => $project->core,
      '%language' => $project->langcode,
    ];
    return strtr($template, $variables);
  }

  /**
   * Determine if a file is a remote file.
   *
   * @param string $uri
   *   The URI or URI pattern of the file.
   *
   * @return bool
   *   TRUE if the $uri is a remote file.
   */
  protected function fileIsRemote($uri): bool {
    $scheme = StreamWrapperManager::getScheme($uri);
    if ($scheme) {
      return !$this->fileSystem->realpath($scheme . '://');
    }
    return FALSE;
  }

}
