<?php

namespace Drupal\locale;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;

/**
 * Provides the locale source services.
 */
class LocaleSource {

  public function __construct(
    protected readonly LocaleProjectStorageInterface $projectStorage,
    protected readonly FileSystemInterface $fileSystem,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Loads cached translation sources containing current translation status.
   *
   * @param array $projects
   *   Array of project names. Defaults to all translatable projects.
   * @param array $langcodes
   *   Array of language codes. Defaults to all translatable languages.
   *
   * @return array
   *   Array of source objects. Keyed with <project name>:<language code>.
   *
   * @see sourceBuild()
   */
  public function loadSources(?array $projects = NULL, ?array $langcodes = NULL): array {
    $sources = [];
    $projects = $projects ?: array_keys($this->projectStorage->getProjects());
    $langcodes = $langcodes ?: array_keys(locale_translatable_language_list());

    // Load source data from locale_translation_status cache.
    $status = locale_translation_get_status();

    // Use only the selected projects and languages for update.
    foreach ($projects as $project) {
      foreach ($langcodes as $langcode) {
        $sources[$project][$langcode] = $status[$project][$langcode] ?? NULL;
      }
    }
    return $sources;
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
   * @see sourceBuild()
   */
  public function buildSources(array $projects = [], array $langcodes = []): array {
    $sources = [];
    $projects = $this->projectStorage->getProjects($projects);
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
      $directory = $source_file->directory;
      $filename = '/' . preg_quote($source_file->filename) . '$/';

      if (is_dir($directory)) {
        if ($files = $this->fileSystem->scanDirectory($directory, $filename, ['key' => 'name', 'recurse' => FALSE])) {
          $file = current($files);
          $source_file->uri = $file->uri;
          $source_file->timestamp = filemtime($file->uri);
          return $source_file;
        }
      }
    }
    return FALSE;
  }

  /**
   * Builds abstract translation source.
   *
   * @param object $project
   *   Project object.
   * @param string $langcode
   *   Language code.
   * @param string $filename
   *   (optional) File name of translation file. May contain placeholders.
   *   Defaults to the default translation filename from the settings.
   *
   * @return object
   *   Source object:
   *   - "project": Project name.
   *   - "name": Project name (inherited from project).
   *   - "language": Language code.
   *   - "core": Core version (inherited from project).
   *   - "version": Project version (inherited from project).
   *   - "project_type": Project type (inherited from project).
   *   - "files": Array of file objects containing properties of local and
   *     remote translation files.
   *   Other processes can add the following properties:
   *   - "type": Most recent translation source found. LOCALE_TRANSLATION_REMOTE
   *      and LOCALE_TRANSLATION_LOCAL indicate available new translations,
   *      LOCALE_TRANSLATION_CURRENT indicate that the current translation is
   *      them most recent. "type" corresponds with a key of the "files" array.
   *   - "timestamp": The creation time of the "type" translation (file).
   *   - "last_checked": The time when the "type" translation was last checked.
   *   The "files" array can hold file objects of type:
   *   LOCALE_TRANSLATION_LOCAL, LOCALE_TRANSLATION_REMOTE and
   *   LOCALE_TRANSLATION_CURRENT. Each contains following properties:
   *   - "type": The object type (LOCALE_TRANSLATION_LOCAL,
   *     LOCALE_TRANSLATION_REMOTE, etc. see above).
   *   - "project": Project name.
   *   - "langcode": Language code.
   *   - "version": Project version.
   *   - "uri": Local or remote file path.
   *   - "directory": Directory of the local po file.
   *   - "filename": File name.
   *   - "timestamp": Timestamp of the file.
   *   - "keep": TRUE to keep the downloaded file.
   */
  public function sourceBuild($project, $langcode, $filename = NULL) {
    // Follow-up issue: https://www.drupal.org/node/1842380.
    // Convert $source object to a TranslatableProject class and use a typed
    // class for $source-file.

    // Create a source object with data of the project object.
    $source = clone $project;
    $source->project = $project->name;
    $source->langcode = $langcode;
    $source->type = '';
    $source->timestamp = 0;
    $source->last_checked = 0;

    $filename = $filename ?: $this->configFactory->get('locale.settings')->get('translation.default_filename');

    // If the server_pattern contains a remote file path we will check for a
    // remote file. The local version of this file will only be checked if a
    // translations directory has been defined. If the server_pattern is a local
    // file path we will only check for a file in the local file system.
    $files = [];
    if ($this->fileIsRemote($source->server_pattern)) {
      $files[LOCALE_TRANSLATION_REMOTE] = (object) [
        'project' => $project->name,
        'langcode' => $langcode,
        'version' => $project->version,
        'type' => LOCALE_TRANSLATION_REMOTE,
        'filename' => $this->buildServerPattern($source, basename($source->server_pattern)),
        'uri' => $this->buildServerPattern($source, $source->server_pattern),
      ];
      $files[LOCALE_TRANSLATION_LOCAL] = (object) [
        'project' => $project->name,
        'langcode' => $langcode,
        'version' => $project->version,
        'type' => LOCALE_TRANSLATION_LOCAL,
        'filename' => $this->buildServerPattern($source, $filename),
        'directory' => 'translations://',
      ];
      $files[LOCALE_TRANSLATION_LOCAL]->uri = $files[LOCALE_TRANSLATION_LOCAL]->directory . $files[LOCALE_TRANSLATION_LOCAL]->filename;
    }
    else {
      $files[LOCALE_TRANSLATION_LOCAL] = (object) [
        'project' => $project->name,
        'langcode' => $langcode,
        'version' => $project->version,
        'type' => LOCALE_TRANSLATION_LOCAL,
        'filename' => $this->buildServerPattern($source, basename($source->server_pattern)),
        'directory' => $this->buildServerPattern($source, $this->fileSystem->dirname($source->server_pattern)),
      ];
      $files[LOCALE_TRANSLATION_LOCAL]->uri = $files[LOCALE_TRANSLATION_LOCAL]->directory . '/' . $files[LOCALE_TRANSLATION_LOCAL]->filename;
    }
    $source->files = $files;

    // If this project+language is already translated, we add its status and
    // update the current translation timestamp and last_updated time. If the
    // project+language is not translated before, create a new record.
    $history = locale_translation_get_file_history();
    if (isset($history[$project->name][$langcode]) && $history[$project->name][$langcode]->timestamp) {
      $source->files[LOCALE_TRANSLATION_CURRENT] = $history[$project->name][$langcode];
      $source->type = LOCALE_TRANSLATION_CURRENT;
      $source->timestamp = $history[$project->name][$langcode]->timestamp;
      $source->last_checked = $history[$project->name][$langcode]->last_checked;
    }
    else {
      locale_translation_update_file_history($source);
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
      '%language' => $project->langcode ?? '%language',
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
