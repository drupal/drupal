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
          'class' => 'admin-note',
          'label' => t('Administrator note'),
          'data' => $settings[$project]['notes'],
        );
      }
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */
