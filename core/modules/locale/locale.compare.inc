<?php

/**
 * @file
 * The API for comparing project translation status with available translation.
 */

use Drupal\Core\Cache;
use Drupal\Core\Utility\ProjectInfo;

/**
 * Load common APIs.
 */
// @todo Combine functions differently in files to avoid unnecessary includes.
// Follow-up issue http://drupal.org/node/1834298
require_once __DIR__ . '/locale.translation.inc';

/**
 * Clear the project data table.
 */
function locale_translation_flush_projects() {
  // Followup issue: http://drupal.org/node/1842362
  // Replace {locale_project} table by \Drupal::state() variable(s).
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
  if (!\Drupal::moduleHandler()->moduleExists('update')) {
    return array();
  }

  // Get the project list based on .info.yml files.
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

      if (!empty($release['version'])) {
        $data['info']['version'] = $release['version'];
      }

      unset($release);
    }

    // For every project store information.
    $data += array(
      'version' => isset($data['info']['version']) ? $data['info']['version'] : '',
      'core' => isset($data['info']['core']) ? $data['info']['core'] : \Drupal::CORE_COMPATIBILITY,
      // A project can provide the path and filename pattern to download the
      // gettext file. Use the default if not.
      'server_pattern' => isset($data['info']['interface translation server pattern']) && $data['info']['interface translation server pattern'] ? $data['info']['interface translation server pattern'] : $default_server['pattern'],
      'status' => !empty($data['project_status']) ? 1 : 0,
    );
    $project = (object) $data;
    $projects[$name] = $project;

    // Create or update the project record.
    db_merge('locale_project')
      ->key('name', $project->name)
      ->fields(array(
        'name' => $project->name,
        'project_type' => $project->project_type,
        'core' => $project->core,
        'version' => $project->version,
        'server_pattern' => $project->server_pattern,
        'status' => $project->status,
      ))
      ->execute();

    // Invalidate the cache of translatable projects.
    locale_translation_clear_cache_projects();
  }
  return $projects;
}

/**
 * Fetch an array of projects for translation update.
 *
 * @return array
 *   Array of project data including .info.yml file data.
 */
function locale_translation_project_list() {
  $projects = &drupal_static(__FUNCTION__, array());
  if (empty($projects)) {
    module_load_include('compare.inc', 'update');
    $config = \Drupal::config('locale.settings');
    $projects = array();

    $additional_whitelist = array(
      'interface translation project',
      'interface translation server pattern',
    );
    $module_data = _locale_translation_prepare_project_list(system_rebuild_module_data(), 'module');
    $theme_data = _locale_translation_prepare_project_list(system_rebuild_theme_data(), 'theme');
    $project_info = new ProjectInfo();
    $project_info->processInfoList($projects, $module_data, 'module', TRUE, $additional_whitelist);
    $project_info->processInfoList($projects, $theme_data, 'theme', TRUE, $additional_whitelist);

    // Allow other modules to alter projects before fetching and comparing.
    \Drupal::moduleHandler()->alter('locale_translation_projects', $projects);
  }
  return $projects;
}

/**
 * Prepare module and theme data.
 *
 * Modify .info.yml file data before it is processed by
 * \Drupal\Core\Utility\ProjectInfo->processInfoList(). In order for
 * \Drupal\Core\Utility\ProjectInfo->processInfoList() to recognize a project,
 * it requires the 'project' parameter in the .info.yml file data.
 *
 * Custom modules or themes can bring their own gettext translation file. To
 * enable import of this file the module or theme defines "interface translation
 * project = myproject" in its .info.yml file. This function will add a project
 * "myproject" to the info data.
 *
 * @param \Drupal\Core\Extension\Extension[] $data
 *   Array of .info.yml file data.
 * @param string $type
 *   The project type. i.e. module, theme.
 *
 * @return array
 *   Array of .info.yml file data.
 */
function _locale_translation_prepare_project_list($data, $type) {
  foreach ($data as $name => $file) {
    // Include interface translation projects. To allow
    // \Drupal\Core\Utility\ProjectInfo->processInfoList() to identify this as
    // a project the 'project' property is filled with the
    // 'interface translation project' value.
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
 *   - "server_pattern": URI containing po file pattern.
 */
function locale_translation_default_translation_server() {
  $pattern = \Drupal::config('locale.settings')->get('translation.default_server_pattern');
  // An additional check is required here. During the upgrade process
  // \Drupal::config()->get() returns NULL. We use the defined value as
  // fallback.
  $pattern  = $pattern ? $pattern : LOCALE_TRANSLATION_DEFAULT_SERVER_PATTERN;

  return array(
    'pattern' => $pattern,
  );
}

/**
 * Check for the latest release of project translations.
 *
 * @param array $projects
 *   Array of project names to check. Defaults to all translatable projects.
 * @param string $langcodes
 *   Array of language codes. Defaults to all translatable languages.
 *
 * @return array
 *   Available sources indexed by project and language.
 *
 * @todo Return batch or NULL.
 */
function locale_translation_check_projects($projects = array(), $langcodes = array()) {
  if (locale_translation_use_remote_source()) {
    // Retrieve the status of both remote and local translation sources by
    // using a batch process.
    locale_translation_check_projects_batch($projects, $langcodes);
  }
  else {
    // Retrieve and save the status of local translations only.
    locale_translation_check_projects_local($projects, $langcodes);
    \Drupal::state()->set('locale.translation_last_checked', REQUEST_TIME);
  }
}

/**
 * Gets and stores the status and timestamp of remote po files.
 *
 * A batch process is used to check for po files at remote locations and (when
 * configured) to check for po files in the local file system. The most recent
 * translation source states are stored in the state variable
 * 'locale.translation_status'.
 *
 * @param array $projects
 *   Array of project names to check. Defaults to all translatable projects.
 * @param string $langcodes
 *   Array of language codes. Defaults to all translatable languages.
 */
function locale_translation_check_projects_batch($projects = array(), $langcodes = array()) {
  // Build and set the batch process.
  $batch = locale_translation_batch_status_build($projects, $langcodes);
  batch_set($batch);
}

/**
 * Builds a batch to get the status of remote and local translation files.
 *
 * The batch process fetches the state of both local and (if configured) remote
 * translation files. The data of the most recent translation is stored per
 * per project and per language. This data is stored in a state variable
 * 'locale.translation_status'. The timestamp it was last updated is stored
 * in the state variable 'locale.translation_last_checked'.
 *
 * @param array $projects
 *   Array of project names for which to check the state of translation files.
 *   Defaults to all translatable projects.
 * @param array $langcodes
 *   Array of language codes. Defaults to all translatable languages.
 *
 * @return array
 *   Batch definition array.
 */
function locale_translation_batch_status_build($projects = array(), $langcodes = array()) {
  $projects = $projects ? $projects : array_keys(locale_translation_get_projects());
  $langcodes = $langcodes ? $langcodes : array_keys(locale_translatable_language_list());
  $options = _locale_translation_default_update_options();

  $operations = _locale_translation_batch_status_operations($projects, $langcodes, $options);

  $batch = array(
    'operations' => $operations,
    'title' => t('Checking translations'),
    'progress_message' => '',
    'finished' => 'locale_translation_batch_status_finished',
    'error_message' => t('Error checking translation updates.'),
    'file' => drupal_get_path('module', 'locale') . '/locale.batch.inc',
  );
  return $batch;
}

/**
 * Helper function to construct batch operations checking remote translation
 * status.
 *
 * @param array $projects
 *   Array of project names to be processed.
 * @param array $langcodes
 *   Array of language codes.
 * @param array $options
 *   Batch processing options.
 *
 * @return array
 *   Array of batch operations.
 */
function _locale_translation_batch_status_operations($projects, $langcodes, $options = array()) {
  $operations = array();

  foreach ($projects as $project) {
    foreach ($langcodes as $langcode) {
      // Check status of local and remote translation sources.
      $operations[] = array('locale_translation_batch_status_check', array($project, $langcode, $options));
    }
  }

  return $operations;
}

/**
 * Check and store the status and timestamp of local po files.
 *
 * Only po files in the local file system are checked. Any remote translation
 * files will be ignored.
 *
 * Projects may contain a server_pattern option containing a pattern of the
 * path to the po source files. If no server_pattern is defined the default
 * translation directory is checked for the po file. When a server_pattern is
 * defined the specified location is checked. The server_pattern can be set in
 * the module's .info.yml file or by using
 * hook_locale_translation_projects_alter().
 *
 * @param array $projects
 *   Array of project names for which to check the state of translation files.
 *   Defaults to all translatable projects.
 * @param array $langcodes
 *   Array of language codes. Defaults to all translatable languages.
 */
function locale_translation_check_projects_local($projects = array(), $langcodes = array()) {
  $projects = locale_translation_get_projects($projects);
  $langcodes = $langcodes ? $langcodes : array_keys(locale_translatable_language_list());

  // For each project and each language we check if a local po file is
  // available. When found the source object is updated with the appropriate
  // type and timestamp of the po file.
  foreach ($projects as $name => $project) {
    foreach ($langcodes as $langcode) {
      $source = locale_translation_source_build($project, $langcode);
      $file = locale_translation_source_check_file($source);
      locale_translation_status_save($name, $langcode, LOCALE_TRANSLATION_LOCAL, $file);
    }
  }
}
