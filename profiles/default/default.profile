<?php
// $Id$

/**
 * Return an array of the modules to be enabled when this profile is installed.
 *
 * @return
 *   An array of modules to enable.
 */
function default_profile_modules() {
  return array('block', 'color', 'comment', 'help', 'image', 'menu', 'path', 'taxonomy', 'dblog', 'search', 'toolbar');
}

/**
 * Return a description of the profile for the initial installation screen.
 *
 * @return
 *   An array with keys 'name' and 'description' describing this profile,
 *   and optional 'language' to override the language selection for
 *   language-specific profiles.
 */
function default_profile_details() {
  return array(
    'name' => 'Drupal',
    'description' => 'Create a Drupal site with the most commonly used features pre-installed.'
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
 * The installer goes through the profile-select -> locale-select
 * -> requirements -> database -> profile-install-batch
 * -> locale-initial-batch -> configure -> locale-remaining-batch
 * -> finished -> done tasks, in this order, if you don't implement
 * this function in your profile.
 *
 * If this function is implemented, you can have any number of
 * custom tasks to perform after 'configure', implementing a state
 * machine here to walk the user through those tasks. First time,
 * this function gets called with $task set to 'profile', and you
 * can advance to further tasks by setting $task to your tasks'
 * identifiers, used as array keys in the hook_profile_task_list()
 * above. You must avoid the reserved tasks listed in
 * install_reserved_tasks(). If you implement your custom tasks,
 * this function will get called in every HTTP request (for form
 * processing, printing your information screens and so on) until
 * you advance to the 'profile-finished' task, with which you
 * hand control back to the installer. Each custom page you
 * return needs to provide a way to continue, such as a form
 * submission or a link. You should also set custom page titles.
 *
 * You should define the list of custom tasks you implement by
 * returning an array of them in hook_profile_task_list(), as these
 * show up in the list of tasks on the installer user interface.
 *
 * Remember that the user will be able to reload the pages multiple
 * times, so you might want to use variable_set() and variable_get()
 * to remember your data and control further processing, if $task
 * is insufficient. Should a profile want to display a form here,
 * it can; the form should set '#redirect' to FALSE, and rely on
 * an action in the submit handler, such as variable_set(), to
 * detect submission and proceed to further tasks. See the configuration
 * form handling code in install_tasks() for an example.
 *
 * Important: Any temporary variables should be removed using
 * variable_del() before advancing to the 'profile-finished' phase.
 *
 * @param $task
 *   The current $task of the install system. When hook_profile_tasks()
 *   is first called, this is 'profile'.
 * @param $url
 *   Complete URL to be used for a link or form action on a custom page,
 *   if providing any, to allow the user to proceed with the installation.
 *
 * @return
 *   An optional HTML string to display to the user. Only used if you
 *   modify the $task, otherwise discarded.
 */
function default_profile_tasks(&$task, $url) {
  
  // Enable some standard blocks.
  $values = array(
    array(
      'module' => 'system',
      'delta' => 'main',
      'theme' => 'garland',
      'status' => 1,
      'weight' => 0,
      'region' => 'content',
      'pages' => '',
      'cache' => -1,
    ),
    array(
      'module' => 'user',
      'delta' => 'login',
      'theme' => 'garland',
      'status' => 1,
      'weight' => 0,
      'region' => 'left',
      'pages' => '',
      'cache' => -1,
    ),
    array(
      'module' => 'system',
      'delta' => 'navigation',
      'theme' => 'garland',
      'status' => 1,
      'weight' => 0,
      'region' => 'left',
      'pages' => '',
      'cache' => -1,
    ),
    array(
      'module' => 'system',
      'delta' => 'management',
      'theme' => 'garland',
      'status' => 1,
      'weight' => 1,
      'region' => 'left',
      'pages' => '',
      'cache' => -1,
    ),
    array(
      'module' => 'system',
      'delta' => 'powered-by',
      'theme' => 'garland',
      'status' => 1,
      'weight' => 10,
      'region' => 'footer',
      'pages' => '',
      'cache' => -1,
    ),
    array(
      'module' => 'system',
      'delta' => 'help',
      'theme' => 'garland',
      'status' => 1,
      'weight' => 0,
      'region' => 'help',
      'pages' => '',
      'cache' => -1,
    ),
  );
  $query = db_insert('block')->fields(array('module', 'delta', 'theme', 'status', 'weight', 'region', 'pages', 'cache'));
  foreach ($values as $record) {
    $query->values($record);
  }
  $query->execute();  

  // Insert default user-defined node types into the database. For a complete
  // list of available node type attributes, refer to the node type API
  // documentation at: http://api.drupal.org/api/HEAD/function/hook_node_info.
  $types = array(
    array(
      'type' => 'page',
      'name' => st('Page'),
      'base' => 'node_content',
      'description' => st("Use <em>pages</em> for your static content, such as an 'About us' page."),
      'custom' => 1,
      'modified' => 1,
      'locked' => 0,
    ),
    array(
      'type' => 'article',
      'name' => st('Article'),
      'base' => 'node_content',
      'description' => st('Use <em>articles</em> for time-specific content like news, press releases or blog posts.'),
      'custom' => 1,
      'modified' => 1,
      'locked' => 0,
    ),
  );

  foreach ($types as $type) {
    $type = node_type_set_defaults($type);
    node_type_save($type);
  }

  // Default page to not be promoted and have comments disabled.
  variable_set('node_options_page', array('status'));
  variable_set('comment_page', COMMENT_NODE_HIDDEN);

  // Don't display date and author information for page nodes by default.
  variable_set('node_submitted_page', FALSE);

  // Create an image style.
  $style = array('name' => 'thumbnail');
  $style = image_style_save($style);
  $effect = array(
    'isid' => $style['isid'],
    'name' => 'image_scale_and_crop',
    'data' => array('width' => '85', 'height' => '85'),
  );
  image_effect_save($effect);

  // Enable user picture support and set the default to a square thumbnail option.
  variable_set('user_pictures', '1');
  variable_set('user_picture_dimensions', '1024x1024');
  variable_set('user_picture_file_size', '800');
  variable_set('user_picture_style', 'thumbnail');

  $theme_settings = theme_get_settings();
  $theme_settings['toggle_node_user_picture'] = '1';
  $theme_settings['toggle_comment_user_picture'] = '1';
  variable_set('theme_settings', $theme_settings);

  // Create a default vocabulary named "Tags", enabled for the 'article' content type.
  $description = st('Use tags to group articles on similar topics into categories.');
  $help = st('Enter a comma-separated list of words.');

  $vid = db_insert('taxonomy_vocabulary')->fields(array(
    'name' => 'Tags',
    'description' => $description,
    'machine_name' => 'tags',
    'help' => $help,
    'relations' => 0,
    'hierarchy' => 0,
    'multiple' => 0,
    'required' => 0,
    'tags' => 1,
    'module' => 'taxonomy',
    'weight' => 0,
  ))->execute();
  db_insert('taxonomy_vocabulary_node_type')->fields(array('vid' => $vid, 'type' => 'article'))->execute();

  // Create a default role for site administrators.
  $rid = db_insert('role')->fields(array('name' => 'administrator'))->execute();

  // Set this as the administrator role.
  variable_set('user_admin_role', $rid);

  // Assign all available permissions to this role.
  foreach (module_invoke_all('permission') as $key => $value) {
    db_insert('role_permission')
      ->fields(array(
        'rid' => $rid,
        'permission' => $key,
      ))->execute();
  }

  // Update the menu router information.
  menu_rebuild();

  // Save some default links.
  $link = array('link_path' => 'admin/build/menu-customize/main-menu/add', 'link_title' => 'Add a main menu link', 'menu_name' => 'main-menu');
  menu_link_save($link);
}

/**
 * Implement hook_form_alter().
 *
 * Allows the profile to alter the site-configuration form. This is
 * called through custom invocation, so $form_state is not populated.
 */
function default_form_alter(&$form, $form_state, $form_id) {
  if ($form_id == 'install_configure') {
    // Set default for site name field.
    $form['site_information']['site_name']['#default_value'] = $_SERVER['SERVER_NAME'];
  }
}
