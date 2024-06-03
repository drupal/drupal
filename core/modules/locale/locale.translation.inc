<?php

/**
 * @file
 * Common API for interface translation.
 */

use Drupal\Core\StreamWrapper\StreamWrapperManager;

/**
 * Comparison result of source files timestamps.
 *
 * Timestamp of source 1 is less than the timestamp of source 2.
 *
 * @see _locale_translation_source_compare()
 */
const LOCALE_TRANSLATION_SOURCE_COMPARE_LT = -1;

/**
 * Comparison result of source files timestamps.
 *
 * Timestamp of source 1 is equal to the timestamp of source 2.
 *
 * @see _locale_translation_source_compare()
 */
const LOCALE_TRANSLATION_SOURCE_COMPARE_EQ = 0;

/**
 * Comparison result of source files timestamps.
 *
 * Timestamp of source 1 is greater than the timestamp of source 2.
 *
 * @see _locale_translation_source_compare()
 */
const LOCALE_TRANSLATION_SOURCE_COMPARE_GT = 1;

/**
 * Get array of projects which are available for interface translation.
 *
 * This project data contains all projects which will be checked for available
 * interface translations.
 *
 * For full functionality this function depends on Update module.
 * When Update module is enabled the project data will contain the most recent
 * module status; both in enabled status as in version. When Update module is
 * disabled this function will return the last known module state. The status
 * will only be updated once Update module is enabled.
 *
 * @param array $project_names
 *   Array of names of the projects to get.
 *
 * @return array
 *   Array of project data for translation update.
 *
 * @see locale_translation_build_projects()
 */
function locale_translation_get_projects(array $project_names = []) {
  $projects = &drupal_static(__FUNCTION__, []);

  if (empty($projects)) {
    // Get project data from the database.
    $row_count = \Drupal::service('locale.project')->countProjects();
    // https://www.drupal.org/node/1777106 is a follow-up issue to make the
    // check for possible out-of-date project information more robust.
    if ($row_count == 0) {
      \Drupal::moduleHandler()->loadInclude('locale', 'inc', 'locale.compare');
      // At least the core project should be in the database, so we build the
      // data if none are found.
      locale_translation_build_projects();
    }
    $projects = \Drupal::service('locale.project')->getAll();
    array_walk($projects, function (&$project) {
      $project = (object) $project;
    });
  }

  // Return the requested project names or all projects.
  if ($project_names) {
    return array_intersect_key($projects, array_combine($project_names, $project_names));
  }
  return $projects;
}

/**
 * Clears the projects cache.
 */
function locale_translation_clear_cache_projects() {
  drupal_static_reset('locale_translation_get_projects');
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
 */
function locale_translation_load_sources(?array $projects = NULL, ?array $langcodes = NULL) {
  $sources = [];
  $projects = $projects ? $projects : array_keys(locale_translation_get_projects());
  $langcodes = $langcodes ? $langcodes : array_keys(locale_translatable_language_list());

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
 * @see locale_translation_source_build()
 */
function locale_translation_build_sources(array $projects = [], array $langcodes = []) {
  $sources = [];
  $projects = locale_translation_get_projects($projects);
  $langcodes = $langcodes ? $langcodes : array_keys(locale_translatable_language_list());

  foreach ($projects as $project) {
    foreach ($langcodes as $langcode) {
      $source = locale_translation_source_build($project, $langcode);
      $sources[$source->name][$source->langcode] = $source;
    }
  }
  return $sources;
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
 */
function locale_translation_source_check_file($source) {
  if (isset($source->files[LOCALE_TRANSLATION_LOCAL])) {
    $source_file = $source->files[LOCALE_TRANSLATION_LOCAL];
    $directory = $source_file->directory;
    $filename = '/' . preg_quote($source_file->filename) . '$/';

    if (is_dir($directory)) {
      if ($files = \Drupal::service('file_system')->scanDirectory($directory, $filename, ['key' => 'name', 'recurse' => FALSE])) {
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
 */
function locale_translation_source_build($project, $langcode, $filename = NULL) {
  // Follow-up issue: https://www.drupal.org/node/1842380.
  // Convert $source object to a TranslatableProject class and use a typed class
  // for $source-file.

  // Create a source object with data of the project object.
  $source = clone $project;
  $source->project = $project->name;
  $source->langcode = $langcode;
  $source->type = '';
  $source->timestamp = 0;
  $source->last_checked = 0;

  $filename = $filename ? $filename : \Drupal::config('locale.settings')->get('translation.default_filename');

  // If the server_pattern contains a remote file path we will check for a
  // remote file. The local version of this file will only be checked if a
  // translations directory has been defined. If the server_pattern is a local
  // file path we will only check for a file in the local file system.
  $files = [];
  if (_locale_translation_file_is_remote($source->server_pattern)) {
    $files[LOCALE_TRANSLATION_REMOTE] = (object) [
      'project' => $project->name,
      'langcode' => $langcode,
      'version' => $project->version,
      'type' => LOCALE_TRANSLATION_REMOTE,
      'filename' => locale_translation_build_server_pattern($source, basename($source->server_pattern)),
      'uri' => locale_translation_build_server_pattern($source, $source->server_pattern),
    ];
    $files[LOCALE_TRANSLATION_LOCAL] = (object) [
      'project' => $project->name,
      'langcode' => $langcode,
      'version' => $project->version,
      'type' => LOCALE_TRANSLATION_LOCAL,
      'filename' => locale_translation_build_server_pattern($source, $filename),
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
      'filename' => locale_translation_build_server_pattern($source, basename($source->server_pattern)),
      'directory' => locale_translation_build_server_pattern($source, \Drupal::service('file_system')->dirname($source->server_pattern)),
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
function locale_translation_build_server_pattern($project, $template) {
  $variables = [
    '%project' => $project->name,
    '%version' => $project->version,
    '%core' => $project->core,
    '%language' => $project->langcode ?? '%language',
  ];
  return strtr($template, $variables);
}

/**
 * Populate a queue with project to check for translation updates.
 */
function locale_cron_fill_queue() {
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
    $options = _locale_translation_default_update_options();
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
 */
function _locale_translation_file_is_remote($uri) {
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
 */
function _locale_translation_source_compare($source1, $source2) {
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
 */
function _locale_translation_default_update_options() {
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
