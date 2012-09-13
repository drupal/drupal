<?php

/**
 * @file
 * The API for comparing project translation status with available translation.
 */

/**
 * Default location of gettext file on the translation server.
 *
 * @see locale_translation_default_translation_server().
 */
const LOCALE_TRANSLATION_DEFAULT_SERVER_PATTERN = 'http://ftp.drupal.org/files/translations/%core/%project/%project-%version.%language.po';

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
 *   - "%filename": Project file name.
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
    '%filename' => isset($project->filename) ? $project->filename : '%filename',
  );
  return strtr($template, $variables);
}
