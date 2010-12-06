<?php
// $Id$

/**
 * @file
 * Hooks provided by the Update Status module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the list of projects before fetching data and comparing versions.
 *
 * Most modules will never need to implement this hook. It is for advanced
 * interaction with the update status module: mere mortals need not apply.
 * The primary use-case for this hook is to add projects to the list, for
 * example, to provide update status data on disabled modules and themes. A
 * contributed module might want to hide projects from the list, for example,
 * if there is a site-specific module that doesn't have any official releases,
 * that module could remove itself from this list to avoid "No available
 * releases found" warnings on the available updates report. In rare cases, a
 * module might want to alter the data associated with a project already in
 * the list.
 *
 * @param $projects
 *   Reference to an array of the projects installed on the system. This
 *   includes all the metadata documented in the comments below for each
 *   project (either module or theme) that is currently enabled. The array is
 *   initially populated inside update_get_projects() with the help of
 *   _update_process_info_list(), so look there for examples of how to
 *   populate the array with real values.
 *
 * @see update_get_projects()
 * @see _update_process_info_list()
 */
function hook_update_projects_alter(&$projects) {
  // Hide a site-specific module from the list.
  unset($projects['site_specific_module']);

  // Add a disabled module to the list.
  // The key for the array should be the machine-readable project "short name".
  $projects['disabled_project_name'] = array(
    // Machine-readable project short name (same as the array key above).
    'name' => 'disabled_project_name',
    // Array of values from the main .info file for this project.
    'info' => array(
      'name' => 'Some disabled module',
      'description' => 'A module not enabled on the site that you want to see in the available updates report.',
      'version' => '7.x-1.0',
      'core' => '7.x',
      // The maximum file change time (the "ctime" returned by the filectime()
      // PHP method) for all of the .info files included in this project.
      '_info_file_ctime' => 1243888165,
    ),
    // The date stamp when the project was released, if known. If the disabled
    // project was an officially packaged release from drupal.org, this will
    // be included in the .info file as the 'datestamp' field. This only
    // really matters for development snapshot releases that are regenerated,
    // so it can be left undefined or set to 0 in most cases.
    'datestamp' => 1243888185,
    // Any modules (or themes) included in this project. Keyed by machine-
    // readable "short name", value is the human-readable project name printed
    // in the UI.
    'includes' => array(
      'disabled_project' => 'Disabled module',
      'disabled_project_helper' => 'Disabled module helper module',
      'disabled_project_foo' => 'Disabled module foo add-on module',
    ),
    // Does this project contain a 'module', 'theme', 'disabled-module', or
    // 'disabled-theme'?
    'project_type' => 'disabled-module',
  );
}

/**
 * Alter the information about available updates for projects.
 *
 * @param $projects
 *   Reference to an array of information about available updates to each
 *   project installed on the system.
 *
 * @see update_calculate_project_data()
 */
function hook_update_status_alter(&$projects) {
  $settings = variable_get('update_advanced_project_settings', array());
  foreach ($projects as $project => $project_info) {
    if (isset($settings[$project]) && isset($settings[$project]['check']) &&
        ($settings[$project]['check'] == 'never' ||
          (isset($project_info['recommended']) &&
            $settings[$project]['check'] === $project_info['recommended']))) {
      $projects[$project]['status'] = UPDATE_NOT_CHECKED;
      $projects[$project]['reason'] = t('Ignored from settings');
      if (!empty($settings[$project]['notes'])) {
        $projects[$project]['extra'][] = array(
          'class' => array('admin-note'),
          'label' => t('Administrator note'),
          'data' => $settings[$project]['notes'],
        );
      }
    }
  }
}

/**
 * Verify an archive after it has been downloaded and extracted.
 *
 * @param string $project
 *   The short name of the project that has been downloaded.
 * @param string $archive_file
 *   The filename of the unextracted archive.
 * @param string $directory
 *   The directory that the archive was extracted into.
 *
 * @return
 *   If there are any problems, return an array of error messages. If there are
 *   no problems, return an empty array.
 *
 * @see update_manager_archive_verify()
 */
function hook_verify_update_archive($project, $archive_file, $directory) {
  $errors = array();
  if (!file_exists($directory)) {
    $errors[] = t('The %directory does not exist.', array('%directory' => $directory));
  }
  // Add other checks on the archive integrity here.
  return $errors;
}

/**
 * @} End of "addtogroup hooks".
 */
