<?php
// $Id: default.profile,v 1.19 2007/11/06 09:00:31 goba Exp $

/**
 * Return an array of the modules to be enabled when this profile is installed.
 *
 * @return
 *  An array of modules to be enabled.
 */
function default_profile_modules() {
  return array('color', 'comment', 'help', 'menu', 'taxonomy', 'dblog');
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
 *   A keyed array of tasks the profile will perform during
 *   the final stage. The keys of the array will be used internally,
 *   while the values will be displayed to the user in the installer
 *   task list.
 */
function default_profile_task_list() {
}

/**
 * Perform any final installation tasks for this profile.
 *
 * The installer goes through the configure -> locale-import ->
 * locale-batch -> finished -> done tasks in this order, if you
 * don't implement this function in your profile.
 *
 * If this function is implemented, you can have any number of
 * custom tasks to perform, implementing a state machine here to
 * walk the user through those tasks, by setting $task to something
 * other then the reserved tasks listed in install_reserved_tasks()
 * and the 'profile' task this function gets called with for first
 * time. If you implement your custom tasks, this function will get called
 * in every HTTP request (for form processing, printing your
 * information screens and so on) until you advance to the
 * 'locale-import' task, with which you hand control back to the
 * installer.
 *
 * You should define the list of custom tasks you implement by
 * returning an array of them in hook_profile_task_list().
 *
 * Should a profile want to display a form here, it can; it should set
 * the task using variable_set('install_task', 'new_task') and use
 * the form technique used in install_tasks() rather than using
 * drupal_get_form().
 *
 * @param $task
 *   The current $task of the install system. When hook_profile_tasks()
 *   is first called, this is 'profile'.
 *
 * @return
 *   An optional HTML string to display to the user. Only used if you
 *   modify the $task, otherwise discarded.
 */
function default_profile_tasks(&$task) {

  // Insert default user-defined node types into the database. For a complete
  // list of available node type attributes, refer to the node type API
  // documentation at: http://api.drupal.org/api/HEAD/function/hook_node_info.
  $types = array(
    array(
      'type' => 'page',
      'name' => st('Page'),
      'module' => 'node',
      'description' => st("A <em>page</em>, similar in form to a <em>story</em>, is a simple method for creating and displaying information that rarely changes, such as an \"About us\" section of a website. By default, a <em>page</em> entry does not allow visitor comments and is not featured on the site's initial home page."),
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
      'description' => st("A <em>story</em>, similar in form to a <em>page</em>, is ideal for creating and displaying content that informs or engages website visitors. Press releases, site announcements, and informal blog-like entries may all be created with a <em>story</em> entry. By default, a <em>story</em> entry is automatically featured on the site's initial home page, and provides the ability to post comments."),
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

  // Update the menu router information.
  menu_rebuild();
}
