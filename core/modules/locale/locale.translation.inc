<?php

/**
 * @file
 */

@trigger_error(__FILE__ . ' is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. All functions and constants have been deprecated. See https://www.drupal.org/node/3569330', E_USER_DEPRECATED);

use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\locale\LocaleDefaultOptions;
use Drupal\locale\LocaleSource;

/**
 * Comparison result of source files timestamps.
 *
 * Timestamp of source 1 is less than the timestamp of source 2.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0.
 *   There is no replacement.
 *
 * @see https://www.drupal.org/node/3569330
 * @see _locale_translation_source_compare()
 */
const LOCALE_TRANSLATION_SOURCE_COMPARE_LT = -1;

/**
 * Comparison result of source files timestamps.
 *
 * Timestamp of source 1 is equal to the timestamp of source 2.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0.
 *   There is no replacement.
 *
 * @see https://www.drupal.org/node/3569330
 * @see _locale_translation_source_compare()
 */
const LOCALE_TRANSLATION_SOURCE_COMPARE_EQ = 0;

/**
 * Comparison result of source files timestamps.
 *
 * Timestamp of source 1 is greater than the timestamp of source 2.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0.
 *   There is no replacement.
 *
 * @see https://www.drupal.org/node/3569330
 * @see _locale_translation_source_compare()
 */
const LOCALE_TRANSLATION_SOURCE_COMPARE_GT = 1;

/**
 * Get array of projects which are available for interface translation.
 *
 * This project data contains all projects which will be checked for available
 * interface translations.
 *
 * For full functionality this function depends on the Update Status module.
 * When the Update Status module is enabled the project data will contain the
 * most recent module status; both in enabled status as in version. When the
 * Update Status module is disabled this function will return the last known
 * module state. The status will only be updated once the Update Status module
 * is enabled.
 *
 * @param array $project_names
 *   Array of names of the projects to get.
 *
 * @return array
 *   Array of project data for translation update.
 *
 * @see locale_translation_build_projects()
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service('locale.project')->getProjects($project_names) instead.
 *
 * @see https://www.drupal.org/node/3569330
 */
function locale_translation_get_projects(array $project_names = []) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service("locale.project")->getProjects($project_names) instead. See https://www.drupal.org/node/3569330', E_USER_DEPRECATED);
  return \Drupal::service('locale.project')->getProjects($project_names);
}

/**
 * Clears the projects cache.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service('locale.project')->resetCache() instead.
 *
 * @see https://www.drupal.org/node/3569330
 */
function locale_translation_clear_cache_projects(): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(\'locale.project\')->resetCache() instead. See https://www.drupal.org/node/3569330', E_USER_DEPRECATED);
  \Drupal::service('locale.project')->resetCache();
}

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
 * @see locale_translation_source_build()
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleSource::class)
 *   ->loadSources($projects, $langcodes) instead.
 *
 * @see https://www.drupal.org/node/3569330
 */
function locale_translation_load_sources(?array $projects = NULL, ?array $langcodes = NULL): array {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleSource::class)->loadSources($projects, $langcodes) instead. See https://www.drupal.org/node/3569330', E_USER_DEPRECATED);
  return \Drupal::service(LocaleSource::class)->loadSources($projects, $langcodes);
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
 * @see locale_translation_source_build()
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleSource::class)
 *   ->buildSources($projects, $langcodes) instead.
 *
 * @see https://www.drupal.org/node/3569330
 */
function locale_translation_build_sources(array $projects = [], array $langcodes = []): array {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleSource::class)->buildSources($projects, $langcodes) instead. See https://www.drupal.org/node/3569330', E_USER_DEPRECATED);
  return \Drupal::service(LocaleSource::class)->buildSources($projects, $langcodes);
}

/**
 * Checks whether a po file exists in the local filesystem.
 *
 * It will search in the directory set in the translation source. Which defaults
 * to the "translations://" stream wrapper path. The directory may contain any
 * valid stream wrapper.
 *
 * The "local" files property of the source object contains the definition of a
 * po file we are looking for. The file name defaults to
 * %project-%version.%language.po. Per project this value can be overridden
 * using the server_pattern directive in the module's .info.yml file or by using
 * hook_locale_translation_projects_alter().
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
 * @see locale_translation_source_build()
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleSource::class)->sourceCheckFile($source) instead.
 *
 * @see https://www.drupal.org/node/3569330
 */
function locale_translation_source_check_file($source) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleSource::class)->sourceCheckFile($source) instead. See https://www.drupal.org/node/3569330', E_USER_DEPRECATED);
  return \Drupal::service(LocaleSource::class)->sourceCheckFile($source);
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
 *   - "files": Array of file objects containing properties of local and remote
 *     translation files.
 *   Other processes can add the following properties:
 *   - "type": Most recent translation source found. LOCALE_TRANSLATION_REMOTE
 *      and LOCALE_TRANSLATION_LOCAL indicate available new translations,
 *      LOCALE_TRANSLATION_CURRENT indicate that the current translation is them
 *      most recent. "type" corresponds with a key of the "files" array.
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
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleSource::class)
 *   ->sourceBuild($project, $langcode, $filename) instead.
 *
 * @see https://www.drupal.org/node/3569330
 */
function locale_translation_source_build($project, $langcode, $filename = NULL) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleSource::class)->sourceBuild($project, $langcode, $filename) instead. See https://www.drupal.org/node/3569330', E_USER_DEPRECATED);
  return \Drupal::service(LocaleSource::class)->sourceBuild($project, $langcode, $filename);
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
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleSource::class)
 *   ->buildServerPattern($project, $template) instead.
 *
 * @see https://www.drupal.org/node/3569330
 */
function locale_translation_build_server_pattern($project, $template) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleSource::class)->buildServerPattern($project, $template) instead. See https://www.drupal.org/node/3569330', E_USER_DEPRECATED);
  return \Drupal::service(LocaleSource::class)->buildServerPattern($project, $template);
}

/**
 * Populate a queue with project to check for translation updates.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There
 *   is no replacement.
 *
 * @see https://www.drupal.org/node/3569330
 */
function locale_cron_fill_queue(): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no replacement. See https://www.drupal.org/node/3569330', E_USER_DEPRECATED);
  $updates = [];
  $config = \Drupal::config('locale.settings');

  // Determine which project+language should be updated.
  $request_time = \Drupal::time()->getRequestTime();
  $last = $request_time - $config->get('translation.update_interval_days') * 3600 * 24;
  $projects = \Drupal::service('locale.project')->getAll();
  $projects = array_filter($projects, function ($project) {
    return $project['status'] == 1;
  });
  $connection = \Drupal::database();
  $files = $connection->select('locale_file', 'f')
    ->condition('f.project', array_keys($projects), 'IN')
    ->condition('f.last_checked', $last, '<')
    ->fields('f', ['project', 'langcode'])
    ->execute()->fetchAll();
  foreach ($files as $file) {
    $updates[$file->project][] = $file->langcode;

    // Update the last_checked timestamp of the project+language that will
    // be checked for updates.
    $connection->update('locale_file')
      ->fields(['last_checked' => $request_time])
      ->condition('project', $file->project)
      ->condition('langcode', $file->langcode)
      ->execute();
  }

  // For each project+language combination a number of tasks are added to
  // the queue.
  if ($updates) {
    \Drupal::moduleHandler()->loadInclude('locale', 'inc', 'locale.fetch');
    $options = LocaleDefaultOptions::updateOptions();
    $queue = \Drupal::queue('locale_translation', TRUE);

    foreach ($updates as $project => $languages) {
      $batch = locale_translation_batch_update_build([$project], $languages, $options);
      foreach ($batch['operations'] as $item) {
        $queue->createItem($item);
      }
    }
  }
}

/**
 * Determine if a file is a remote file.
 *
 * @param string $uri
 *   The URI or URI pattern of the file.
 *
 * @return bool
 *   TRUE if the $uri is a remote file.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. There
 *   is no replacement.
 *
 * @see https://www.drupal.org/node/3569330
 */
function _locale_translation_file_is_remote($uri) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3569330', E_USER_DEPRECATED);
  $scheme = StreamWrapperManager::getScheme($uri);
  if ($scheme) {
    return !\Drupal::service('file_system')->realpath($scheme . '://');
  }
  return FALSE;
}

/**
 * Compare two update sources, looking for the newer one.
 *
 * The timestamp property of the source objects are used to determine which is
 * the newer one.
 *
 * @param object $source1
 *   Source object of the first translation source.
 * @param object $source2
 *   Source object of available update.
 *
 * @return int
 *   - "LOCALE_TRANSLATION_SOURCE_COMPARE_LT": $source1 < $source2 OR $source1
 *     is missing.
 *   - "LOCALE_TRANSLATION_SOURCE_COMPARE_EQ":  $source1 == $source2 OR both
 *     $source1 and $source2 are missing.
 *   - "LOCALE_TRANSLATION_SOURCE_COMPARE_GT":  $source1 > $source2 OR $source2
 *     is missing.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. There
 *   is no replacement.
 *
 * @see https://www.drupal.org/node/3569330
 */
function _locale_translation_source_compare($source1, $source2) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3569330', E_USER_DEPRECATED);
  if (isset($source1->timestamp) && isset($source2->timestamp)) {
    if ($source1->timestamp == $source2->timestamp) {
      return LOCALE_TRANSLATION_SOURCE_COMPARE_EQ;
    }
    else {
      return $source1->timestamp > $source2->timestamp ? LOCALE_TRANSLATION_SOURCE_COMPARE_GT : LOCALE_TRANSLATION_SOURCE_COMPARE_LT;
    }
  }
  elseif (isset($source1->timestamp) && !isset($source2->timestamp)) {
    return LOCALE_TRANSLATION_SOURCE_COMPARE_GT;
  }
  elseif (!isset($source1->timestamp) && isset($source2->timestamp)) {
    return LOCALE_TRANSLATION_SOURCE_COMPARE_LT;
  }
  else {
    return LOCALE_TRANSLATION_SOURCE_COMPARE_EQ;
  }
}

/**
 * Returns default import options for translation update.
 *
 * @return array
 *   Array of translation import options.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. There
 *   is no replacement.
 *
 * @see https://www.drupal.org/node/3569330
 */
function _locale_translation_default_update_options() {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3569330', E_USER_DEPRECATED);
  $config = \Drupal::config('locale.settings');
  return [
    'customized' => LOCALE_NOT_CUSTOMIZED,
    'overwrite_options' => [
      'not_customized' => $config->get('translation.overwrite_not_customized'),
      'customized' => $config->get('translation.overwrite_customized'),
    ],
    'finish_feedback' => TRUE,
    'use_remote' => locale_translation_use_remote_source(),
  ];
}
