<?php

/**
 * @file
 * The API for comparing project translation status with available translation.
 */

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Utility\ProjectInfo;

/**
 * Load common APIs.
 */
// @todo Combine functions differently in files to avoid unnecessary includes.
// Follow-up issue: https://www.drupal.org/node/1834298.
require_once __DIR__ . '/locale.translation.inc';

/**
 * Clear the project data table.
 */
function locale_translation_flush_projects() {
  \Drupal::service('locale.project')->deleteAll();
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
  // Get the project list based on .info.yml files.
  $projects = locale_translation_project_list();

  // Mark all previous projects as disabled and store new project data.
  \Drupal::service('locale.project')->disableAll();

  $default_server = locale_translation_default_translation_server();

  foreach ($projects as $name => $data) {
    // For dev releases, remove the '-dev' part and trust the translation server
    // to fall back to the latest stable release for that branch.
    if (isset($data['info']['version']) && strpos($data['info']['version'], '-dev')) {
      if (preg_match("/^(\d+\.x-\d+\.).*$/", $data['info']['version'], $matches)) {
        // Example matches: 8.x-1.x-dev, 8.x-1.0-alpha1+5-dev => 8.x-1.x
        $data['info']['version'] = $matches[1] . 'x';
      }
      elseif (preg_match("/^(\d+\.\d+\.).*$/", $data['info']['version'], $matches)) {
        // Example match: 8.0.0-dev => 8.0.x (Drupal core)
        $data['info']['version'] = $matches[1] . 'x';
      }
    }

    // For every project store information.
    $data += [
      'name' => $name,
      'version' => $data['info']['version'] ?? '',
      'core' => 'all',
      // A project can provide the path and filename pattern to download the
      // gettext file. Use the default if not.
      'server_pattern' => isset($data['info']['interface translation server pattern']) && $data['info']['interface translation server pattern'] ? $data['info']['interface translation server pattern'] : $default_server['pattern'],
      'status' => !empty($data['project_status']) ? 1 : 0,
    ];

    $project = (object) $data;
    $projects[$name] = $project;

    // Create or update the project record.
    \Drupal::service('locale.project')->set($project->name, $data);

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
  $projects = &drupal_static(__FUNCTION__, []);
  if (empty($projects)) {
    $projects = [];

    $additional_whitelist = [
      'interface translation project',
      'interface translation server pattern',
    ];
    $module_data = _locale_translation_prepare_project_list(\Drupal::service('extension.list.module')->getList(), 'module');
    $theme_data = _locale_translation_prepare_project_list(\Drupal::service('extension.list.theme')->reset()->getList(), 'theme');
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
 * project = my_project" in its .info.yml file. This function will add a project
 * "my_project" to the info data.
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
 *   - "pattern": URI containing po file pattern.
 */
function locale_translation_default_translation_server() {
  $pattern = \Drupal::config('locale.settings')->get('translation.default_server_pattern');
  // An additional check is required here. During the upgrade process
  // \Drupal::config()->get() returns NULL. We use the defined value as
  // fallback.
  $pattern = $pattern ? $pattern : LOCALE_TRANSLATION_DEFAULT_SERVER_PATTERN;

  return [
    'pattern' => $pattern,
  ];
}

/**
 * Check for the latest release of project translations.
 *
 * @param array $projects
 *   Array of project names to check. Defaults to all translatable projects.
 * @param array $langcodes
 *   Array of language codes. Defaults to all translatable languages.
 *
 * @todo Return batch or NULL.
 */
function locale_translation_check_projects($projects = [], $langcodes = []) {
  if (locale_translation_use_remote_source()) {
    // Retrieve the status of both remote and local translation sources by
    // using a batch process.
    locale_translation_check_projects_batch($projects, $langcodes);
  }
  else {
    // Retrieve and save the status of local translations only.
    locale_translation_check_projects_local($projects, $langcodes);
    \Drupal::state()->set('locale.translation_last_checked', \Drupal::time()->getRequestTime());
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
 * @param array $langcodes
 *   Array of language codes. Defaults to all translatable languages.
 */
function locale_translation_check_projects_batch($projects = [], $langcodes = []) {
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
function locale_translation_batch_status_build($projects = [], $langcodes = []) {
  $projects = $projects ? $projects : array_keys(locale_translation_get_projects());
  $langcodes = $langcodes ? $langcodes : array_keys(locale_translatable_language_list());
  $options = _locale_translation_default_update_options();

  $operations = _locale_translation_batch_status_operations($projects, $langcodes, $options);

  $batch_builder = (new BatchBuilder())
    ->setFile(\Drupal::service('extension.list.module')->getPath('locale') . '/locale.batch.inc')
    ->setTitle(t('Checking translations'))
    ->setErrorMessage(t('Error checking translation updates.'))
    ->setFinishCallback('locale_translation_batch_status_finished');

  array_walk($operations, function ($operation) use ($batch_builder) {
    call_user_func_array([$batch_builder, 'addOperation'], $operation);
  });

  return $batch_builder->toArray();
}

/**
 * Constructs batch operations for checking remote translation status.
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
function _locale_translation_batch_status_operations($projects, $langcodes, $options = []) {
  $operations = [];

  foreach ($projects as $project) {
    foreach ($langcodes as $langcode) {
      // Check version and status translation sources.
      $operations[] = ['locale_translation_batch_version_check', [$project, $langcode]];
      $operations[] = ['locale_translation_batch_status_check', [$project, $langcode, $options]];
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
function locale_translation_check_projects_local($projects = [], $langcodes = []) {
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
