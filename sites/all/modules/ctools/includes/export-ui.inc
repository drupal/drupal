<?php
// $Id: export-ui.inc,v 1.3 2010/12/31 23:50:25 merlinofchaos Exp $

/**
 * @file
 * Provide a tool for creating UIs for exportable objects.
 *
 * See Advanced Help for documentation.
 */

/**
 * Process an export-ui plugin to provide it with defaults.
 */
function ctools_export_ui_process(&$plugin, $info) {
  ctools_include('export');

  $plugin += array(
    'has menu' => TRUE,
    'title' => $plugin['name'],
    'export' => array(),
    'allowed operations' => array(),
    'menu' => array(),
    'form' => array(),
    'strings' => array(),
    'list' => NULL,
    'access' => 'administer site configuration',
  );

  // Provide CRUD access defaults based on the base 'access' setting:
  $plugin += array(
    'create access' => $plugin['access'],
    'delete access' => $plugin['access'],
  );

  if (empty($plugin['has menu'])) {
    return;
  }

  // The following keys are required and the plugin cannot be processed
  // without them.
  $keys = array(
    'title singular',
    'title plural',
    'title singular proper',
    'title plural proper',
    'schema',
  );

  foreach ($keys as $key) {
    if (empty($plugin[$key])) {
      drupal_set_message(t('The plugin definition of @plugin is missing the %key key.', array('%key' => $key, '@plugin' => $plugin['name'])), 'error');
    }
  }

  // If we're on the modules page and building a menu, there is a design flaw
  // in Drupal core that causes modules to be installed but the schema does
  // not become available until AFTER menu rebuild. This helps smooth that
  // out. This is a HACK but it should work:
  $schema = ctools_export_get_schema($plugin['schema']);

  if (empty($schema)) {
    // If we're updating the schema may not have been read yet, so don't report this error in that case.
    if (!defined('MAINTENANCE_MODE')) {
      drupal_set_message(t('The plugin definition of @plugin cannot locate schema %schema.', array('%schema' => $plugin['schema'], '@plugin' => $plugin['name'])), 'error');
    }
    return;
  }

  if (empty($schema['export'])) {
    drupal_set_message(t('The plugin definition of @plugin uses %schema, but it has no export section.', array('%schema' => $plugin['schema'], '@plugin' => $plugin['name'])), 'error');
    return;
  }

  $plugin['export'] += array(
    // Add the identifier key from the schema so we don't have to call
    // ctools_export_get_schema() just for that.
    'key' => $schema['export']['key'],
  );

  // Add some default fields that appear often in exports
  // If these use different keys they can easily be specified in the
  // $plugin.

  if (empty($plugin['export']['admin_title']) && !empty($schema['fields']['admin_title'])) {
    $plugin['export']['admin_title'] = 'admin_title';
  }
  if (empty($plugin['export']['admin_description']) && !empty($schema['fields']['admin_description'])) {
    $plugin['export']['admin_description'] = 'admin_description';
  }

  // Define allowed operations, and the name of the operations.
  $plugin['allowed operations'] += array(
    'edit'    => array('title' => t('Edit')),
    'enable'  => array('title' => t('Enable'), 'ajax' => TRUE, 'token' => TRUE),
    'disable' => array('title' => t('Disable'), 'ajax' => TRUE, 'token' => TRUE),
    'revert'  => array('title' => t('Revert')),
    'delete'  => array('title' => t('Delete')),
    'clone'   => array('title' => t('Clone')),
    'import'  => array('title' => t('Import')),
    'export'  => array('title' => t('Export')),
  );

  $plugin['menu'] += array(
    'menu item' => str_replace(' ', '-', $plugin['name']),
    'menu prefix' => 'admin/structure',
    'menu title' => $plugin['title'],
    'menu description' => '',
  );
  $base_path = ctools_export_ui_plugin_base_path($plugin);
  $prefix_count = count(explode('/', $plugin['menu']['menu prefix']));

  $plugin['menu'] += array(
    // Default menu items that should be declared.
    'items' => array(),
  );

  $plugin['menu']['items'] += array(
    'list callback' => array(
      'path' => '',
      // Menu items are translated by the menu system.
      // TODO: We need more flexibility in title. The title of the admin page
      // is not necessarily the title of the object, plus we need
      // plural, singular, proper, not proper, etc.
      'title' => $plugin['menu']['menu title'],
      'description' => $plugin['menu']['menu description'],
      'page callback' => 'ctools_export_ui_switcher_page',
      'page arguments' => array($plugin['name'], 'list'),
      'access callback' => 'ctools_export_ui_task_access',
      'access arguments' => array($plugin['name'], 'list'),
      'type' => MENU_NORMAL_ITEM,
    ),
    'list' => array(
      'path' => 'list',
      'title' => 'List',
      'page callback' => 'ctools_export_ui_switcher_page',
      'page arguments' => array($plugin['name'], 'list'),
      'access callback' => 'ctools_export_ui_task_access',
      'access arguments' => array($plugin['name'], 'list'),
      'type' => MENU_DEFAULT_LOCAL_TASK,
      'weight' => -10,
    ),
    'add' => array(
      'path' => 'add',
      'title' => 'Add',
      'page callback' => 'ctools_export_ui_switcher_page',
      'page arguments' => array($plugin['name'], 'add'),
      'access callback' => 'ctools_export_ui_task_access',
      'access arguments' => array($plugin['name'], 'add'),
      'type' => MENU_LOCAL_ACTION,
    ),
    'edit callback' => array(
      'path' => 'list/%ctools_export_ui',
      'page callback' => 'ctools_export_ui_switcher_page',
      'page arguments' => array($plugin['name'], 'edit', $prefix_count + 2),
      'load arguments' => array($plugin['name']),
      'access callback' => 'ctools_export_ui_task_access',
      'access arguments' => array($plugin['name'], 'edit', $prefix_count + 2),
      'type' => MENU_CALLBACK,
    ),
    'edit' => array(
      'path' => 'list/%ctools_export_ui/edit',
      'title' => 'Edit',
      'page callback' => 'ctools_export_ui_switcher_page',
      'page arguments' => array($plugin['name'], 'edit', $prefix_count + 2),
      'load arguments' => array($plugin['name']),
      'access callback' => 'ctools_export_ui_task_access',
      'access arguments' => array($plugin['name'], 'edit', $prefix_count + 2),
      'type' => MENU_DEFAULT_LOCAL_TASK,
    ),
  );

  if ($plugin['allowed operations']['import']) {
    $plugin['menu']['items'] += array(
      'import' => array(
        'path' => 'import',
        'title' => 'Import',
        'page callback' => 'ctools_export_ui_switcher_page',
        'page arguments' => array($plugin['name'], 'import'),
        'access callback' => 'ctools_export_ui_task_access',
        'access arguments' => array($plugin['name'], 'import'),
        'type' => MENU_LOCAL_ACTION,
      ),
    );
  }

  if ($plugin['allowed operations']['export']) {
    $plugin['menu']['items'] += array(
      'export' => array(
        'path' => 'list/%ctools_export_ui/export',
        'title' => 'Export',
        'page callback' => 'ctools_export_ui_switcher_page',
        'page arguments' => array($plugin['name'], 'export', $prefix_count + 2),
        'load arguments' => array($plugin['name']),
        'access callback' => 'ctools_export_ui_task_access',
        'access arguments' => array($plugin['name'], 'export', $prefix_count + 2),
        'type' => MENU_LOCAL_TASK,
      ),
    );
  }

  if ($plugin['allowed operations']['revert']) {
    $plugin['menu']['items'] += array(
      'revert' => array(
        'path' => 'list/%ctools_export_ui/revert',
        'title' => 'Revert',
        'page callback' => 'ctools_export_ui_switcher_page',
        // Note: Yes, 'delete' op is correct.
        'page arguments' => array($plugin['name'], 'delete', $prefix_count + 2),
        'load arguments' => array($plugin['name']),
        'access callback' => 'ctools_export_ui_task_access',
        'access arguments' => array($plugin['name'], 'revert', $prefix_count + 2),
        'type' => MENU_CALLBACK,
      ),
    );
  }

  if ($plugin['allowed operations']['delete']) {
    $plugin['menu']['items'] += array(
      'delete' => array(
        'path' => 'list/%ctools_export_ui/delete',
        'title' => 'Delete',
        'page callback' => 'ctools_export_ui_switcher_page',
        'page arguments' => array($plugin['name'], 'delete', $prefix_count + 2),
        'load arguments' => array($plugin['name']),
        'access callback' => 'ctools_export_ui_task_access',
        'access arguments' => array($plugin['name'], 'delete', $prefix_count + 2),
        'type' => MENU_CALLBACK,
      ),
    );
  }

  if ($plugin['allowed operations']['clone']) {
    $plugin['menu']['items'] += array(
      'clone' => array(
        'path' => 'list/%ctools_export_ui/clone',
        'title' => 'Clone',
        'page callback' => 'ctools_export_ui_switcher_page',
        'page arguments' => array($plugin['name'], 'clone', $prefix_count + 2),
        'load arguments' => array($plugin['name']),
        'access callback' => 'ctools_export_ui_task_access',
        'access arguments' => array($plugin['name'], 'clone', $prefix_count + 2),
        'type' => MENU_CALLBACK,
      ),
    );
  }

  if ($plugin['allowed operations']['enable']) {
    $plugin['menu']['items'] += array(
      'enable' => array(
        'path' => 'list/%ctools_export_ui/enable',
        'title' => 'Enable',
        'page callback' => 'ctools_export_ui_switcher_page',
        'page arguments' => array($plugin['name'], 'enable', $prefix_count + 2),
        'load arguments' => array($plugin['name']),
        'access callback' => 'ctools_export_ui_task_access',
        'access arguments' => array($plugin['name'], 'enable', $prefix_count + 2),
        'type' => MENU_CALLBACK,
      ),
    );
  }

  if ($plugin['allowed operations']['disable']) {
    $plugin['menu']['items'] += array(
      'disable' => array(
        'path' => 'list/%ctools_export_ui/disable',
        'title' => 'Disable',
        'page callback' => 'ctools_export_ui_switcher_page',
        'page arguments' => array($plugin['name'], 'disable', $prefix_count + 2),
        'load arguments' => array($plugin['name']),
        'access callback' => 'ctools_export_ui_task_access',
        'access arguments' => array($plugin['name'], 'disable', $prefix_count + 2),
        'type' => MENU_CALLBACK,
      ),
    );
  }

  // Define some redirects that should happen after edit/add/clone operations.
  $plugin['redirect'] = array(
    'add' => $base_path,
    'clone' => $base_path,
    'edit' => $base_path,
    'import' => $base_path,
  );

  // Define form elements.
  $plugin['form'] += array(
    'settings' => function_exists($plugin['name'] . '_form') ? $plugin['name'] . '_form' : '',
    'validate' => function_exists($plugin['name'] . '_form_validate') ? $plugin['name'] . '_form_validate' : '',
    'submit' => function_exists($plugin['name'] . '_form_submit') ? $plugin['name'] . '_form_submit' : '',
  );

  // Define strings.

  // For all strings, %title may be filled in at a later time via str_replace
  // since we do not know the title now.
  $plugin['strings'] += array(
    'title' => array(),
    'confirmation' => array(),
    'help' => array(),
    'message' => array(),
  );

  // Strings used in drupal_set_title().
  $plugin['strings']['title'] += array(
    'add' => t('Add a new @plugin', array('@plugin' => $plugin['title singular'])),
    // The "%title" will be replaced in ctools_export_ui_form(), as in this
    // stage we dont have the specific exportable object.
    'edit' => t('Edit @plugin %title', array('@plugin' => $plugin['title singular'])),
    'clone' => t('Clone @plugin %title', array('@plugin' => $plugin['title singular'])),

    'import' => t('Import @plugin', array('@plugin' => $plugin['title singular'])),
    'export' => t('Export @plugin %title', array('@plugin' => $plugin['title singular'])),
  );

  // Strings used in confirmation pages.
  $plugin['strings']['confirmation'] += array(
    'revert' => array(),
    'delete' => array(),
    'add' => array(),
    'edit' => array(),
  );

  $plugin['strings']['confirmation']['revert'] += array(
    'question' => t('Are you sure you want to revert %title?'),
    'information' => t('This action will permanently remove any customizations made to this item.'),
    'success' => t('The item has been reverted.'),
  );

  $plugin['strings']['confirmation']['delete'] += array(
    'question' => t('Are you sure you want to delete %title?'),
    'information' => t('This action will permanently remove this item from your database..'),
    'success' => t('The item has been deleted.'),
  );

  $plugin['strings']['confirmation']['add'] += array(
    'success' => t('%title has been created.'),
    'fail' => t('%title could not be created.'),
  );

  $plugin['strings']['confirmation']['edit'] += array(
    'success' => t('%title has been updated.'),
    'fail' => t('%title could not be updated.'),
  );

  // Strings used in $forms.
  $plugin['strings']['help'] += array(
    'import' => t('You can import an exported definition by pasting the exported object code into the field below.'),
  );

  // Strings used in drupal_set_message().
  $plugin['strings']['message'] += array(
    'enable' => t('@plugin %title was enabled.', array('@plugin' => $plugin['title singular proper'])),
    'disable' => t('@plugin %title was disabled.', array('@plugin' => $plugin['title singular proper'])),
  );
}

/**
 * Get the class to handle creating a list of exportable items.
 *
 * If a plugin does not define a lister class at all, then the default
 * lister class will be used.
 *
 * @return
 *   Either the lister class or FALSE if one could not be had.
 */
function ctools_export_ui_get_handler($plugin) {
  $cache = &drupal_static(__FUNCTION__, array());
  if (empty($cache[$plugin['name']])) {
    // If a list class is not specified by the plugin, fall back to the
    // default ctools_export_ui plugin instead.
    if (empty($plugin['handler'])) {
      $default = ctools_get_export_ui('ctools_export_ui');
      $class = ctools_plugin_get_class($default, 'handler');
    }
    else {
      $class = ctools_plugin_get_class($plugin, 'handler');
    }

    if ($class) {
      $cache[$plugin['name']] = new $class();
      $cache[$plugin['name']]->init($plugin);
    }
  }
  return !empty($cache[$plugin['name']]) ? $cache[$plugin['name']] : FALSE;
}

/**
 * Get the base path from a plugin.
 *
 * @param $plugin
 *   The plugin.
 *
 * @return
 *   The menu path to the plugin's list.
 */
function ctools_export_ui_plugin_base_path($plugin) {
  return $plugin['menu']['menu prefix'] . '/' . $plugin['menu']['menu item'];
}

/**
 * Get the path to a specific menu item from a plugin.
 *
 * @param $plugin
 *   The plugin name.
 * @param $item_id
 *   The id in the menu items from the plugin.
 * @param $export_key
 *   The export key of the item being edited, if it exists.
 * @return
 *   The menu path to the plugin's list.
 */
function ctools_export_ui_plugin_menu_path($plugin, $item_id, $export_key = NULL) {
  $path = $plugin['menu']['items'][$item_id]['path'];
  if ($export_key) {
    $path = str_replace('%ctools_export_ui', $export_key, $path);
  }
  return ctools_export_ui_plugin_base_path($plugin) . '/' . $path;
}

/**
 * Helper function to include CTools plugins and get an export-ui exportable.
 *
 * @param $plugin_name
 *   The plugin that should be laoded.
 */
function ctools_get_export_ui($plugin_name) {
  ctools_include('plugins');
  return ctools_get_plugins('ctools', 'export_ui', $plugin_name);

}

/**
 * Helper function to include CTools plugins and get all export-ui exportables.
 */
function ctools_get_export_uis() {
  ctools_include('plugins');
  return ctools_get_plugins('ctools', 'export_ui');
}

/**
 * Main page callback to manipulate exportables.
 *
 * This simply loads the object defined in the plugin and hands it off to
 * a method based upon the name of the operation in use. This can easily
 * be used to add more ops.
 */
function ctools_export_ui_switcher_page($plugin_name, $op) {
  $args = func_get_args();
  $js = !empty($_REQUEST['js']);

  // Load the $plugin information
  $plugin = ctools_get_export_ui($plugin_name);
  $handler = ctools_export_ui_get_handler($plugin);

  if ($handler) {
    $method = $op . '_page';
    if (method_exists($handler, $method)) {
      // replace the first two arguments:
      $args[0] = $js;
      $args[1] = $_POST;
      return call_user_func_array(array($handler, $method), $args);
    }
  }
  else {
    return t('Configuration error. No handler found.');
  }
}
