<?php
// $Id: default.profile,v 1.10 2007/05/10 19:55:24 dries Exp $

/**
 * Return an array of the modules to be enabled when this profile is installed.
 *
 * @return
 *  An array of modules to be enabled.
 */
function default_profile_modules() {
  return array('color', 'comment', 'help', 'taxonomy', 'dblog');
}

/**
 * Return a description of the profile for the initial installation screen.
 *
 * @return
 *   An array with keys 'name' and 'description' describing this profile.
 */
function default_profile_details() {
  return array(
    'name' => 'Drupal',
    'description' => 'Select this profile to enable some basic Drupal functionality and the default theme.'
  );
}

/**
 * Return a list of tasks that this profile supports.
 *
 * @return
 *   A keyed array of tasks the profile will perform during the _final stage.
 */
function default_profile_task_list() {
}

/**
 * Perform any final installation tasks for this profile.
 *
 * You can implement a state machine here to walk the user through
 * more tasks, by setting $task to something other then the reserved
 * 'configure', 'finished' and 'done' values. The installer goes
 * through the configure-finished-done tasks in this order, if you
 * don't modify $task. If you implement your custom tasks, this
 * function will get called in every HTTP request (for form
 * processing, printing your information screens and so on) until
 * you advance to the 'finished' or 'done' tasks. Once ready with
 * your profile's tasks, set $task to 'finished' and optionally
 * return a final message to be included on the default final
 * install page. Alternatively you can set $task to 'done' and
 * return a completely custom finished page. In both cases, you
 * hand the control back to the installer.
 *
 * Should a profile want to display a form here, it can; it should set
 * the task using variable_set('install_task', 'new_task') and use
 * the form technique used in install_tasks() rather than using
 * drupal_get_form().
 *
 * @param $task
 *   The current $task of the install system. When hook_profile_final()
 *   is first called, this is 'configure' (the last built-in task of
 *   the Drupal installer).
 *
 * @return
 *   An optional HTML string to display to the user. Used as part of the
 *   completed page if $task is set to 'finished', or used to display a
 *   complete page in all other cases.
 */
function default_profile_final(&$task) {

  // Insert default user-defined node types into the database. For a complete
  // list of available node type attributes, refer to the node type API
  // documentation at: http://api.drupal.org/api/HEAD/function/hook_node_info.
  $types = array(
    array(
      'type' => 'page',
      'name' => st('Page'),
      'module' => 'node',
      'description' => st('If you want to add a static page, like a contact page or an about page, use a page.'),
      'custom' => TRUE,
      'modified' => TRUE,
      'locked' => FALSE,
      'help' => '',
      'min_word_count' => '',
    ),
    array(
      'type' => 'story',
      'name' => st('Story'),
      'module' => 'node',
      'description' => st('Stories are articles in their simplest form: they have a title, a teaser and a body, but can be extended by other modules. The teaser is part of the body too. Stories may be used as a personal blog or for news articles.'),
      'custom' => TRUE,
      'modified' => TRUE,
      'locked' => FALSE,
      'help' => '',
      'min_word_count' => '',
    ),
  );

  foreach ($types as $type) {
    $type = (object) _node_type_set_defaults($type);
    node_type_save($type);
  }

  // Default page to not be promoted and have comments disabled.
  variable_set('node_options_page', array('status'));
  variable_set('comment_page', COMMENT_NODE_DISABLED);

  // Don't display date and author information for page nodes by default.
  $theme_settings = variable_get('theme_settings', array());
  $theme_settings['toggle_node_info_page'] = FALSE;
  variable_set('theme_settings', $theme_settings);

  // Let the installer know we're finished:
  $task = 'finished';
}
