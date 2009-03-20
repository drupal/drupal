<?php
// $Id$

/**
 * Return an array of the modules to be enabled when this profile is installed.
 *
 * @return
 *   An array of modules to enable.
 */
function expert_profile_modules() {
  return array('block', 'dblog');
}

/**
 * Return a description of the profile for the initial installation screen.
 *
 * @return
 *   An array with keys 'name' and 'description' describing this profile,
 *   and optional 'language' to override the language selection for
 *   language-specific profiles.
 */
function expert_profile_details() {
  return array(
    'name' => 'Drupal (minimal)',
    'description' => 'Create a Drupal site with only required modules enabled.'
  );
}

/**
 * Return a list of tasks that this profile supports.
 *
 * @return
 *   A keyed array of tasks the profile will perform during
 *   the final stage. The keys of the array will be used internally,
 *   while the values will be displayed to the user in the installer
 *   task list.
 */
function expert_profile_task_list() {
}

/**
 * Perform any final installation tasks for this profile.
 */
function expert_profile_tasks(&$task, $url) {
  // Enable 3 standard blocks.
  db_query("INSERT INTO {block} (module, delta, theme, status, weight, region, pages, cache) VALUES ('%s', '%s', '%s', %d, %d, '%s', '%s', %d)", 'user', 'login', 'garland', 1, 0, 'left', '', -1);
  db_query("INSERT INTO {block} (module, delta, theme, status, weight, region, pages, cache) VALUES ('%s', '%s', '%s', %d, %d, '%s', '%s', %d)", 'system', 'navigation', 'garland', 1, 0, 'left', '', -1);
  db_query("INSERT INTO {block} (module, delta, theme, status, weight, region, pages, cache) VALUES ('%s', '%s', '%s', %d, %d, '%s', '%s', %d)", 'system', 'management', 'garland', 1, 1, 'left', '', -1);
}

/**
 * Implementation of hook_form_alter().
 *
 * Allows the profile to alter the site-configuration form. This is
 * called through custom invocation, so $form_state is not populated.
 */
function expert_form_alter(&$form, $form_state, $form_id) {
  if ($form_id == 'install_configure') {
    // Set default for site name field.
    $form['site_information']['site_name']['#default_value'] = $_SERVER['SERVER_NAME'];
  }
}
