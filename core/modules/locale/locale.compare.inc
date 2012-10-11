<?php

/**
 * @file
 * The API for comparing project translation status with available translation.
 */

/**
 * Threshold for timestamp comparison.
 *
 * Eliminates a difference between the download time and the actual .po file
 * timestamp in seconds. The download time is stored in the database in
 * {locale_file}.timestamp.
 */
const LOCALE_TRANSLATION_TIMESTAMP_THRESHOLD = 2;

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

use Drupal\Core\Cache;

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
 * @see locale_translation_build_projects().
 *
 * @return array
 *   Array of project data for translation update. See
 *   locale_translation_build_projects() for details.
 */
function locale_translation_get_projects() {
  $projects = &drupal_static(__FUNCTION__, array());

  if (empty($projects)) {
    // Get project data from the database.
    $result = db_query('SELECT name, project_type, core, version, server_pattern, status FROM {locale_project}');

    // http://drupal.org/node/1777106 is a follow-up issue to make the check for
    // possible out-of-date project information more robust.
    if ($result->rowCount() == 0 && module_exists('update')) {
      // At least the core project should be in the database, so we build the
      // data if none are found.
      locale_translation_build_projects();
      $result = db_query('SELECT name, project_type, core, version, server_pattern, status FROM {locale_project}');
    }

    foreach ($result as $project) {
      $projects[$project->name] = $project;
    }
  }
  return $projects;
}

/**
 * Clear the project data table.
 */
function locale_translation_flush_projects() {
  db_truncate('locale_project')->execute();
}

/**
 * Builds list of projects and stores the result in the database.
 *
 * The project data is based on the project list supplied by the Update module.
 * Only the properties required by Locale module is included and additional
 * (custom) modules and translation server data is added.
 *
 * In case the Update module is disabled this function will return an empty
 * array.
 *
 * @return array
 *   Array of project data:
 *   - "name": Project system name.
 *   - "project_type": Project type, e.g. 'module', 'theme'.
 *   - "core": Core release version, e.g. 8.x
 *   - "version": Project release version, e.g. 8.x-1.0
 *     See http://drupalcode.org/project/drupalorg.git/blob/refs/heads/7.x-3.x:/drupalorg_project/plugins/release_packager/DrupalorgProjectPackageRelease.class.php#l219
 *     for how the version strings are created.
 *   - "server_pattern": Translation server po file pattern.
 *   - "status": Project status, 1 = enabled.
 */
function locale_translation_build_projects() {
  // This function depends on Update module. We degrade gracefully.
  if (!module_exists('update')) {
    return array();
  }

  // Get the project list based on .info files.
  $projects = locale_translation_project_list();

  // Mark all previous projects as disabled and store new project data.
  db_update('locale_project')
    ->fields(array(
      'status' => 0,
    ))
    ->execute();

  $default_server = locale_translation_default_translation_server();

  // If project is a dev release, or core, find the latest available release.
  $project_updates = update_get_available(TRUE);
  foreach ($projects as $name => $data) {
    if (isset($project_updates[$name]['releases']) && $project_updates[$name]['project_status'] != 'not-fetched') {
      // Find out if a dev version is installed.
      if (preg_match("/^[0-9]+\.x-([0-9]+)\..*-dev$/", $data['info']['version'], $matches)) {
        // Find a suitable release to use as alternative translation.
        foreach ($project_updates[$name]['releases'] as $project_release) {
          // The first release with the same major release number which is not a
          // dev release is the one. Releases are sorted the most recent first.
          // @todo http://drupal.org/node/1774024 Make a helper function.
          if ($project_release['version_major'] == $matches[1] &&
              (!isset($project_release['version_extra']) || $project_release['version_extra'] != 'dev')) {
            $release = $project_release;
            break;
          }
        }
      }
      // If project is not a dev version, but is core, pick latest release.
      elseif ($name == "drupal") {
        // Pick latest available release.
        $release = array_shift($project_updates[$name]['releases']);
      }

      if (!empty($release['version'])) {
        $data['info']['version'] = $release['version'];
      }

      unset($release);
    }

    // For every project store information.
    $data += array(
      'version' => isset($data['info']['version']) ? $data['info']['version'] : '',
      'core' => isset($data['info']['core']) ? $data['info']['core'] : DRUPAL_CORE_COMPATIBILITY,
      // A project can provide the path and filename pattern to download the
      // gettext file. Use the default if not.
      'server_pattern' => isset($data['info']['interface translation server pattern']) ? $data['info']['interface translation server pattern'] : $default_server['pattern'],
      'status' => !empty($data['project_status']) ? 1 : 0,
    );
    $project = (object) $data;
    $projects[$name] = $project;

    // Create or update the project record.
    db_merge('locale_project')
      ->key(array('name' => $project->name))
      ->fields(array(
        'name' => $project->name,
        'project_type' => $project->project_type,
        'core' => $project->core,
        'version' => $project->version,
        'server_pattern' => $project->server_pattern,
        'status' => $project->status,
      ))
      ->execute();
  }
  return $projects;
}

/**
 * Fetch an array of projects for translation update.
 *
 * @return array
 *   Array of project data including .info file data.
 */
function locale_translation_project_list() {
  // This function depends on Update module. We degrade gracefully.
  if (!module_exists('update')) {
    return array();
  }

  $projects = &drupal_static(__FUNCTION__, array());
  if (empty($projects)) {
    module_load_include('compare.inc', 'update');
    $config = config('locale.settings');
    $projects = array();

    $additional_whitelist = array(
      'interface translation project',
      'interface translation server pattern',
    );
    $module_data = _locale_translation_prepare_project_list(system_rebuild_module_data(), 'module');
    $theme_data = _locale_translation_prepare_project_list(system_rebuild_theme_data(), 'theme');
    update_process_info_list($projects, $module_data, 'module', TRUE, $additional_whitelist);
    update_process_info_list($projects, $theme_data, 'theme', TRUE, $additional_whitelist);
    if ($config->get('translation.check_disabled_modules')) {
      update_process_info_list($projects, $module_data, 'module', FALSE, $additional_whitelist);
      update_process_info_list($projects, $theme_data, 'theme', FALSE, $additional_whitelist);
    }

    // Allow other modules to alter projects before fetching and comparing.
    drupal_alter('locale_translation_projects', $projects);
  }
  return $projects;
}

/**
 * Prepare module and theme data.
 *
 * Modify .info file data before it is processed by update_process_info_list().
 * In order for update_process_info_list() to recognize a project, it requires
 * the 'project' parameter in the .info file data.
 *
 * Custom modules or themes can bring their own gettext translation file. To
 * enable import of this file the module or theme defines "interface translation
 * project = myproject" in its .info file. This function will add a project
 * "myproject" to the info data.
 *
 * @param array $data
 *   Array of .info file data.
 * @param string $type
 *   The project type. i.e. module, theme.
 *
 * @return array
 *   Array of .info file data.
 */
function _locale_translation_prepare_project_list($data, $type) {
  foreach ($data as $name => $file) {
    // Include interface translation projects. To allow
    // update_process_info_list() to identify this as a project the 'project'
    // property is filled with the 'interface translation project' value.
    if (isset($file->info['interface translation project'])) {
      $data[$name]->info['project'] = $file->info['interface translation project'];
    }
  }
  return $data;
}

/**
 * Retrieve data for default server.
 *
 * @return array
 *   Array of server parameters:
 *   - "server_pattern": URL containing po file pattern.
 */
function locale_translation_default_translation_server() {
  $config = config('locale.settings');
  return array(
    'pattern' => $config->get('translation.default_server_pattern'),
  );
}

/**
 * Build path to translation source, out of a server path replacement pattern.
 *
 * @param stdClass $project
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
  $variables = array(
    '%project' => $project->name,
    '%version' => $project->version,
    '%core' => $project->core,
    '%language' => isset($project->language) ? $project->language : '%language',
  );
  return strtr($template, $variables);
}

/**
 * Check for the latest release of project translations.
 *
 * @param array $projects
 *   Projects to check (objects).
 * @param string $langcodes
 *   Array of language codes to check for. Leave empty to check all languages.
 *
 * @return array
 *   Available sources indexed by project and language.
 */
function locale_translation_check_projects($projects, $langcodes = NULL) {
  module_load_include('batch.inc', 'locale');

  if (config('locale.settings')->get('translation.use_source') == LOCALE_TRANSLATION_USE_SOURCE_REMOTE_AND_LOCAL) {
    // Retrieve the status of both remote and local translation sources by
    // using a batch process.
    locale_translation_check_projects_batch($projects, $langcodes);
  }
  else {
    // Retrieve and save the status of local translations only.
    locale_translation_check_projects_local($projects, $langcodes);
  }
}

/**
 * Gets and stores the status and timestamp of remote po files.
 *
 * A batch process is used to check for po files at remote locations and (when
 * configured) to check for po files in the local file system. The most recent
 * translation source states are stored in the state variable
 * 'locale_translation_status'.
 *
 * @params array $projects
 *   Array of translatable projects.
 * @params array $langcodes
 *   Array of language codes to check for. Leave empty to check all languages.
 */
function locale_translation_check_projects_batch($projects, $langcodes = NULL) {
  $langcodes = $langcodes ? $langcodes : array_keys(locale_translatable_language_list());
  $sources = array();
  foreach ($projects as $name => $project) {
    foreach ($langcodes as $langcode) {
      $source = locale_translation_source_build($project, $langcode);
      $sources[] = $source;
    }
  }

  // Build and set the batch process.
  module_load_include('batch.inc', 'locale');
  $batch = locale_translation_batch_status_build($sources);
  batch_set($batch);
}

/**
 * Check and store the status and timestamp of local po files.
 *
 * Only po files in the local file system are checked. Any remote translation
 * sources will be ignored. Results are stored in the state variable
 * 'locale_translation_status'.
 *
 * Projects may contain a server_pattern option containing a pattern of the
 * path to the po source files. If no server_pattern is defined the default
 * translation directory is checked for the po file. When a server_pattern is
 * defined the specified location is checked. The server_pattern can be set in
 * the module's .info file or by using hook_locale_translation_projects_alter().
 *
 * @params array $projects
 *   Array of translatable projects.
 * @params array $langcodes
 *   Array of language codes to check for. Leave empty to check all languages.
 */
function locale_translation_check_projects_local($projects, $langcodes = NULL) {
  $langcodes = $langcodes ? $langcodes : array_keys(locale_translatable_language_list());
  $results = array();

  // For each project and each language we check if a local po file is
  // available. When found the source object is updated with the appropriate
  // type and timestamp of the po file.
  foreach ($projects as $name => $project) {
    foreach ($langcodes as $langcode) {
      $source = locale_translation_source_build($project, $langcode);
      if (locale_translation_source_check_file($source)) {
        $source->type = 'local';
        $source->timestamp = $source->files['local']->timestamp;
        $results[$name][$langcode] = $source;
      }
    }
  }

  state()->set('locale_translation_status', $results);
  state()->set('locale_translation_status_last_update', REQUEST_TIME);
}

/**
 * Check whether a po file exists in the local filesystem.
 *
 * It will search in the directory set in the translation source. Which defaults
 * to the "translations://" stream wrapper path. The directory may contain any
 * valid stream wrapper.
 *
 * The "local" files property of the source object contains the definition of a
 * po file we are looking for. The file name defaults to
 * LOCALE_TRANSLATION_DEFAULT_FILENAME. Per project this value
 * can be overridden using the server_pattern directive in the module's .info
 * file or by using hook_locale_translation_projects_alter().
 *
 * @param stdClass $source
 *   Translation source object.
 *   @see locale_translation_source_build()
 *
 * @return stdClass
 *   File object (filename, basename, name) updated with data of the po file.
 *   On success the files property of the source object is updated.
 *   files['local']:
 *   - "uri": File name and path.
 *   - "timestamp": Last updated time of the po file.
 *   FALSE if the file is not found.
 */
function locale_translation_source_check_file(&$source) {
  if (isset($source->files['local'])) {
    $directory = $source->files['local']->directory;
    $filename = '/' . preg_quote($source->files['local']->filename) . '$/';

    // If the directory contains a stream wrapper, it is converted to a real
    // path. This is required for file_scan_directory() which can not handle
    // stream wrappers.
    if ($scheme = file_uri_scheme($directory)) {
      $directory = str_replace($scheme . '://', drupal_realpath($scheme . '://'), $directory);
    }

    if ($files = file_scan_directory($directory, $filename, array('key' => 'name'))) {
      $file = current($files);
      $source->files['local']->uri = $file->uri;
      $source->files['local']->timestamp = filemtime($file->uri);
      return $file;
    }
  }
  return FALSE;
}

/**
 * Build abstract translation source.
 *
 * @param stdClass $project
 *   Project object.
 * @param string $langcode
 *   Language code.
 * @param string $filename
 *   File name of translation file. May contains placeholders.
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
 *   - "type": Most recent file type 'remote' or 'local'. Corresponding with
 *     a key of the "files" array.
 *   - "timestamp": Timestamp of the most recent translation file.
 *   The "files" array contains file objects with the following properties:
 *   - "uri": Local file path.
 *   - "url": Remote file URL for downloads.
 *   - "directory": Directory of the local po file.
 *   - "filename": File name.
 *   - "timestamp": Timestamp of the file.
 *   - "keep": TRUE to keep the downloaded file.
 */
// @todo Move this file?
function locale_translation_source_build($project, $langcode, $filename = NULL) {
  // Create a source object with data of the project object.
  $source = clone $project;
  $source->project = $project->name;
  $source->language = $langcode;

  $filename = $filename ? $filename : config('locale.settings')->get('translation.default_filename');

  // If the server_pattern contains a remote file path we will check for a
  // remote file. The local version of this file will will only be checked is a
  // translations directory has been defined. If the server_pattern is a local
  // file path we will only check for a file in the local file system.
  $files = array();
  if (_locale_translation_file_is_remote($source->server_pattern)) {
    $files['remote'] = (object) array(
      'type' => 'remote',
      'filename' => locale_translation_build_server_pattern($source, basename($source->server_pattern)),
      'url' => locale_translation_build_server_pattern($source, $source->server_pattern),
    );
    if (variable_get('locale_translate_file_directory', conf_path() . '/files/translations')) {
      $files['local'] = (object) array(
        'type' => 'local',
        'directory' => 'translations://',
        'filename' => locale_translation_build_server_pattern($source, $filename),
      );
    }
  }
  else {
    $files['local'] = (object) array(
      'type' => 'local',
      'directory' => locale_translation_build_server_pattern($source, drupal_dirname($source->server_pattern)),
      'filename' => locale_translation_build_server_pattern($source, basename($source->server_pattern)),
    );
  }
  $source->files = $files;

  return $source;
}

/**
 * Determine if a file is a remote file.
 *
 * @param string $url
 *   The URL or URL pattern of the file.
 *
 * @return boolean
 *   TRUE if the $url is a remote file.
 */
function _locale_translation_file_is_remote($url) {
  $scheme = file_uri_scheme($url);
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
 * @param stdClass $source1
 *   Source object of the first translation source.
 * @param stdClass $source2
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
    if (abs($source1->timestamp - $source2->timestamp) < LOCALE_TRANSLATION_TIMESTAMP_THRESHOLD) {
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
