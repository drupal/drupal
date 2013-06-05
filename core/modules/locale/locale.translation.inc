<?php

/**
 * @file
 * Common API for interface translation.
 */

/**
 * Comparison result of source files timestamps.
 *
 * Timestamp of source 1 is less than the timestamp of source 2.
 * @see _locale_translation_source_compare()
 */
const LOCALE_TRANSLATION_SOURCE_COMPARE_LT = -1;

/**
 * Comparison result of source files timestamps.
 *
 * Timestamp of source 1 is equal to the timestamp of source 2.
 * @see _locale_translation_source_compare()
 */
const LOCALE_TRANSLATION_SOURCE_COMPARE_EQ = 0;

/**
 * Comparison result of source files timestamps.
 *
 * Timestamp of source 1 is greater than the timestamp of source 2.
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
 *  @params array $project_names
 *    Array of names of the projects to get.
 *
 * @return array
 *   Array of project data for translation update.
 *
 * @see locale_translation_build_projects()
 */
function locale_translation_get_projects($project_names = array()) {
  $projects = &drupal_static(__FUNCTION__, array());

  if (empty($projects)) {
    // Get project data from the database.
    $result = db_query('SELECT name, project_type, core, version, server_pattern, status FROM {locale_project}');

    // http://drupal.org/node/1777106 is a follow-up issue to make the check for
    // possible out-of-date project information more robust.
    if ($result->rowCount() == 0 && module_exists('update')) {
      module_load_include('compare.inc', 'locale');
      // At least the core project should be in the database, so we build the
      // data if none are found.
      locale_translation_build_projects();
      $result = db_query('SELECT name, project_type, core, version, server_pattern, status FROM {locale_project}');
    }

    foreach ($result as $project) {
      $projects[$project->name] = $project;
    }
  }

  // Return the requested project names or all projects.
  if ($project_names) {
    return array_intersect_key($projects, drupal_map_assoc($project_names));
  }
  return $projects;
}

/**
 * Clears the projects cache.
 */
function locale_translation_clear_cache_projects() {
  drupal_static('locale_translation_get_projects', array());
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
function locale_translation_load_sources($projects = NULL, $langcodes = NULL) {
  $sources = array();
  $projects = $projects ? $projects : array_keys(locale_translation_get_projects());
  $langcodes = $langcodes ? $langcodes : array_keys(locale_translatable_language_list());

  // Load source data from locale_translation_status cache.
  $status = Drupal::state()->get('locale.translation_status');

  // Use only the selected projects and languages for update.
  foreach($projects as $project) {
    foreach ($langcodes as $langcode) {
      $sources[$project . ':' . $langcode] = isset($status[$project][$langcode]) ? $status[$project][$langcode] : NULL;
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
 *   Array of source objects. Keyed with <project name>:<language code>.
 *
 * @see locale_translation_source_build()
 */
function locale_translation_build_sources($projects = array(), $langcodes = array()) {
  $sources = array();
  $projects = locale_translation_get_projects($projects);
  $langcodes = $langcodes ? $langcodes : array_keys(locale_translatable_language_list());

  foreach ($projects as $project) {
    foreach ($langcodes as $langcode) {
      $source = locale_translation_source_build($project, $langcode);
      $sources[$source->name . ':' . $source->langcode] = $source;
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
 * @return stdClass
 *   File object (filename, basename, name) updated with data of the po file.
 *   On success the files property of the source object is updated.
 *   files[LOCALE_TRANSLATION_LOCAL]:
 *   - "uri": File name and path.
 *   - "timestamp": Last updated time of the po file.
 *   FALSE if the file is not found.
 *
 * @see locale_translation_source_build()
 */
function locale_translation_source_check_file(&$source) {
  if (isset($source->files[LOCALE_TRANSLATION_LOCAL])) {
    $directory = $source->files[LOCALE_TRANSLATION_LOCAL]->directory;
    $filename = '/' . preg_quote($source->files[LOCALE_TRANSLATION_LOCAL]->filename) . '$/';

    // If the directory contains a stream wrapper, it is converted to a real
    // path. This is required for file_scan_directory() which can not handle
    // stream wrappers.
    if ($scheme = file_uri_scheme($directory)) {
      $directory = str_replace($scheme . '://', drupal_realpath($scheme . '://'), $directory);
    }

    if ($files = file_scan_directory($directory, $filename, array('key' => 'name', 'recurse' => FALSE))) {
      $file = current($files);
      $source->files[LOCALE_TRANSLATION_LOCAL]->uri = $file->uri;
      $source->files[LOCALE_TRANSLATION_LOCAL]->timestamp = filemtime($file->uri);
      return $file;
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
 *   File name of translation file. May contain placeholders.
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
 *   - "type": Most recent file type LOCALE_TRANSLATION_REMOTE or
 *      LOCALE_TRANSLATION_LOCAL. Corresponding with a key of the
 *      "files" array.
 *   - "timestamp": Timestamp of the most recent translation file.
 *   The "files" array can hold file objects of type:
 *   LOCALE_TRANSLATION_LOCAL, LOCALE_TRANSLATION_REMOTE,
 *   LOCALE_TRANSLATION_DOWNLOADED, LOCALE_TRANSLATION_IMPORTED and
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
  // Followup issue: http://drupal.org/node/1842380
  // Convert $source object to a TranslatableProject class and use a typed class
  // for $source-file.

  // Create a source object with data of the project object.
  $source = clone $project;
  $source->project = $project->name;
  $source->langcode = $langcode;

  $filename = $filename ? $filename : config('locale.settings')->get('translation.default_filename');

  // If the server_pattern contains a remote file path we will check for a
  // remote file. The local version of this file will only be checked if a
  // translations directory has been defined. If the server_pattern is a local
  // file path we will only check for a file in the local file system.
  $files = array();
  if (_locale_translation_file_is_remote($source->server_pattern)) {
    $files[LOCALE_TRANSLATION_REMOTE] = (object) array(
      'project' => $project->name,
      'langcode' => $langcode,
      'version' => $project->version,
      'type' => LOCALE_TRANSLATION_REMOTE,
      'filename' => locale_translation_build_server_pattern($source, basename($source->server_pattern)),
      'uri' => locale_translation_build_server_pattern($source, $source->server_pattern),
    );
    if (config('locale.settings')->get('translation.path')) {
      $files[LOCALE_TRANSLATION_LOCAL] = (object) array(
        'project' => $project->name,
        'langcode' => $langcode,
        'version' => $project->version,
        'type' => LOCALE_TRANSLATION_LOCAL,
        'filename' => locale_translation_build_server_pattern($source, $filename),
        'directory' => 'translations://',
      );
      $files[LOCALE_TRANSLATION_LOCAL]->uri = $files[LOCALE_TRANSLATION_LOCAL]->directory . $files[LOCALE_TRANSLATION_LOCAL]->filename;
    }
  }
  else {
    $files[LOCALE_TRANSLATION_LOCAL] = (object) array(
      'project' => $project->name,
      'langcode' => $langcode,
      'version' => $project->version,
      'type' => LOCALE_TRANSLATION_LOCAL,
      'filename' => locale_translation_build_server_pattern($source, basename($source->server_pattern)),
      'directory' => locale_translation_build_server_pattern($source, drupal_dirname($source->server_pattern)),
    );
    $files[LOCALE_TRANSLATION_LOCAL]->uri = $files[LOCALE_TRANSLATION_LOCAL]->directory . '/' . $files[LOCALE_TRANSLATION_LOCAL]->filename;
  }
  $source->files = $files;

  return $source;
}

/**
 * Determine if a file is a remote file.
 *
 * @param string $uri
 *   The URI or URI pattern of the file.
 *
 * @return boolean
 *   TRUE if the $uri is a remote file.
 */
function _locale_translation_file_is_remote($uri) {
  $scheme = file_uri_scheme($uri);
  if ($scheme) {
    return !drupal_realpath($scheme . '://');
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
 * @return integer
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
  $config = config('locale.settings');
  return array(
    'customized' => LOCALE_NOT_CUSTOMIZED,
    'overwrite_options' => array(
      'not_customized' => $config->get('translation.overwrite_not_customized'),
      'customized' => $config->get('translation.overwrite_customized'),
    ),
  );
}
