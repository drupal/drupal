<?php
// $Id$

/**
 * Implement hook_profile_tasks().
 */
function default_profile_tasks() {
  $tasks = array(
    'default_profile_site_setup' => array(),
  );
  return $tasks;
}

/**
 * Installation task; perform actions to set up the site for this profile.
 *
 * This task does not return any output, meaning that control will be passed
 * along to the next task without ending the page request.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 */
function default_profile_site_setup(&$install_state) {

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
    array(
      'module' => 'system',
      'delta' => 'main',
      'theme' => 'seven',
      'status' => 1,
      'weight' => 0,
      'region' => 'content',
      'pages' => '',
      'cache' => -1,
    ),
    array(
      'module' => 'system',
      'delta' => 'help',
      'theme' => 'seven',
      'status' => 1,
      'weight' => 0,
      'region' => 'help',
      'pages' => '',
      'cache' => -1,
    ),
    array(
      'module' => 'user',
      'delta' => 'login',
      'theme' => 'seven',
      'status' => 1,
      'weight' => 10,
      'region' => 'content',
      'pages' => '',
      'cache' => -1,
    ),
    array(
      'module' => 'system',
      'delta' => 'main',
      'theme' => 'seven',
      'status' => 1,
      'weight' => 0,
      'region' => 'content',
      'pages' => '',
      'cache' => -1,
    ),
    array(
      'module' => 'system',
      'delta' => 'help',
      'theme' => 'seven',
      'status' => 1,
      'weight' => 0,
      'region' => 'help',
      'pages' => '',
      'cache' => -1,
    ),
    array(
      'module' => 'user',
      'delta' => 'login',
      'theme' => 'seven',
      'status' => 1,
      'weight' => 10,
      'region' => 'content',
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
  $help = st('Enter a comma-separated list of words to describe your content.');

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
  $link = array('link_path' => 'admin/structure/menu-customize/main-menu/add', 'link_title' => 'Add a main menu link', 'menu_name' => 'main-menu');
  menu_link_save($link);

  // Enable the admin theme.
  db_update('system')
    ->fields(array('status' => 1))
    ->condition('type', 'theme')
    ->condition('name', 'seven')
    ->execute();
  variable_set('admin_theme', 'seven');
  variable_set('node_admin_theme', '1');
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
