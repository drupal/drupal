<?php

/**
 * @file
 * API for the Drupal menu system.
 */

/**
 * @defgroup menu Menu system
 * @{
 * Define the navigation menus, and route page requests to code based on URLs.
 *
 * The Drupal menu system drives both the navigation system from a user
 * perspective and the callback system that Drupal uses to respond to URLs
 * passed from the browser. For this reason, a good understanding of the
 * menu system is fundamental to the creation of complex modules.
 *
 * Drupal's menu system follows a simple hierarchy defined by paths.
 * Implementations of hook_menu() define menu items and assign them to
 * paths (which should be unique). The menu system aggregates these items
 * and determines the menu hierarchy from the paths. For example, if the
 * paths defined were a, a/b, e, a/b/c/d, f/g, and a/b/h, the menu system
 * would form the structure:
 * - a
 *   - a/b
 *     - a/b/c/d
 *     - a/b/h
 * - e
 * - f/g
 * Note that the number of elements in the path does not necessarily
 * determine the depth of the menu item in the tree.
 *
 * When responding to a page request, the menu system looks to see if the
 * path requested by the browser is registered as a menu item with a
 * callback. If not, the system searches up the menu tree for the most
 * complete match with a callback it can find. If the path a/b/i is
 * requested in the tree above, the callback for a/b would be used.
 *
 * The found callback function is called with any arguments specified
 * in the "page arguments" attribute of its menu item. The
 * attribute must be an array. After these arguments, any remaining
 * components of the path are appended as further arguments. In this
 * way, the callback for a/b above could respond to a request for
 * a/b/i differently than a request for a/b/j.
 *
 * For an illustration of this process, see page_example.module.
 *
 * Access to the callback functions is also protected by the menu system.
 * The "access callback" with an optional "access arguments" of each menu
 * item is called before the page callback proceeds. If this returns TRUE,
 * then access is granted; if FALSE, then access is denied. Default local task
 * menu items (see next paragraph) may omit this attribute to use the value
 * provided by the parent item.
 *
 * In the default Drupal interface, you will notice many links rendered as
 * tabs. These are known in the menu system as "local tasks", and they are
 * rendered as tabs by default, though other presentations are possible.
 * Local tasks function just as other menu items in most respects. It is
 * convention that the names of these tasks should be short verbs if
 * possible. In addition, a "default" local task should be provided for
 * each set. When visiting a local task's parent menu item, the default
 * local task will be rendered as if it is selected; this provides for a
 * normal tab user experience. This default task is special in that it
 * links not to its provided path, but to its parent item's path instead.
 * The default task's path is only used to place it appropriately in the
 * menu hierarchy.
 *
 * Everything described so far is stored in the menu_router table. The
 * menu_links table holds the visible menu links. By default these are
 * derived from the same hook_menu definitions, however you are free to
 * add more with menu_link_save().
 */

/**
 * @defgroup menu_flags Menu flags
 * @{
 * Flags for use in the "type" attribute of menu items.
 */

define('MENU_IS_ROOT', 0x0001);
define('MENU_VISIBLE_IN_TREE', 0x0002);
define('MENU_VISIBLE_IN_BREADCRUMB', 0x0004);
define('MENU_LINKS_TO_PARENT', 0x0008);
define('MENU_MODIFIED_BY_ADMIN', 0x0020);
define('MENU_CREATED_BY_ADMIN', 0x0040);
define('MENU_IS_LOCAL_TASK', 0x0080);

/**
 * @} End of "Menu flags".
 */

/**
 * @defgroup menu_item_types Menu item types
 * @{
 * Definitions for various menu item types.
 *
 * Menu item definitions provide one of these constants, which are shortcuts for
 * combinations of the above flags.
 */

/**
 * Normal menu items show up in the menu tree and can be moved/hidden by
 * the administrator. Use this for most menu items. It is the default value if
 * no menu item type is specified.
 */
define('MENU_NORMAL_ITEM', MENU_VISIBLE_IN_TREE | MENU_VISIBLE_IN_BREADCRUMB);

/**
 * Callbacks simply register a path so that the correct function is fired
 * when the URL is accessed. They are not shown in the menu.
 */
define('MENU_CALLBACK', MENU_VISIBLE_IN_BREADCRUMB);

/**
 * Modules may "suggest" menu items that the administrator may enable. They act
 * just as callbacks do until enabled, at which time they act like normal items.
 * Note for the value: 0x0010 was a flag which is no longer used, but this way
 * the values of MENU_CALLBACK and MENU_SUGGESTED_ITEM are separate.
 */
define('MENU_SUGGESTED_ITEM', MENU_VISIBLE_IN_BREADCRUMB | 0x0010);

/**
 * Local tasks are rendered as tabs by default. Use this for menu items that
 * describe actions to be performed on their parent item. An example is the path
 * "node/52/edit", which performs the "edit" task on "node/52".
 */
define('MENU_LOCAL_TASK', MENU_IS_LOCAL_TASK);

/**
 * Every set of local tasks should provide one "default" task, that links to the
 * same path as its parent when clicked.
 */
define('MENU_DEFAULT_LOCAL_TASK', MENU_IS_LOCAL_TASK | MENU_LINKS_TO_PARENT);

/**
 * @} End of "Menu item types".
 */

/**
 * @defgroup menu_status_codes Menu status codes
 * @{
 * Status codes for menu callbacks.
 */

define('MENU_FOUND', 1);
define('MENU_NOT_FOUND', 2);
define('MENU_ACCESS_DENIED', 3);
define('MENU_SITE_OFFLINE', 4);

/**
 * @} End of "Menu status codes".
 */

/**
 * @defgroup menu_tree_parameters Menu tree parameters
 * @{
 * Parameters for a menu tree.
 */

 /**
 * The maximum number of path elements for a menu callback
 */
define('MENU_MAX_PARTS', 7);


/**
 * The maximum depth of a menu links tree - matches the number of p columns.
 */
define('MENU_MAX_DEPTH', 9);


/**
 * @} End of "Menu tree parameters".
 */

/**
 * Returns the ancestors (and relevant placeholders) for any given path.
 *
 * For example, the ancestors of node/12345/edit are:
 * - node/12345/edit
 * - node/12345/%
 * - node/%/edit
 * - node/%/%
 * - node/12345
 * - node/%
 * - node
 *
 * To generate these, we will use binary numbers. Each bit represents a
 * part of the path. If the bit is 1, then it represents the original
 * value while 0 means wildcard. If the path is node/12/edit/foo
 * then the 1011 bitstring represents node/%/edit/foo where % means that
 * any argument matches that part.  We limit ourselves to using binary
 * numbers that correspond the patterns of wildcards of router items that
 * actually exists.  This list of 'masks' is built in menu_rebuild().
 *
 * @param $parts
 *   An array of path parts, for the above example
 *   array('node', '12345', 'edit').
 * @return
 *   An array which contains the ancestors and placeholders. Placeholders
 *   simply contain as many '%s' as the ancestors.
 */
function menu_get_ancestors($parts) {
  $number_parts = count($parts);
  $placeholders = array();
  $ancestors = array();
  $length =  $number_parts - 1;
  $end = (1 << $number_parts) - 1;
  $masks = variable_get('menu_masks', array());
  // Only examine patterns that actually exist as router items (the masks).
  foreach ($masks as $i) {
    if ($i > $end) {
      // Only look at masks that are not longer than the path of interest.
      continue;
    }
    elseif ($i < (1 << $length)) {
      // We have exhausted the masks of a given length, so decrease the length.
      --$length;
    }
    $current = '';
    for ($j = $length; $j >= 0; $j--) {
      if ($i & (1 << $j)) {
        $current .= $parts[$length - $j];
      }
      else {
        $current .= '%';
      }
      if ($j) {
        $current .= '/';
      }
    }
    $placeholders[] = "'%s'";
    $ancestors[] = $current;
  }
  return array($ancestors, $placeholders);
}

/**
 * The menu system uses serialized arrays stored in the database for
 * arguments. However, often these need to change according to the
 * current path. This function unserializes such an array and does the
 * necessary change.
 *
 * Integer values are mapped according to the $map parameter. For
 * example, if unserialize($data) is array('view', 1) and $map is
 * array('node', '12345') then 'view' will not be changed because
 * it is not an integer, but 1 will as it is an integer. As $map[1]
 * is '12345', 1 will be replaced with '12345'. So the result will
 * be array('node_load', '12345').
 *
 * @param @data
 *   A serialized array.
 * @param @map
 *   An array of potential replacements.
 * @return
 *   The $data array unserialized and mapped.
 */
function menu_unserialize($data, $map) {
  if ($data = unserialize($data)) {
    foreach ($data as $k => $v) {
      if (is_int($v)) {
        $data[$k] = isset($map[$v]) ? $map[$v] : '';
      }
    }
    return $data;
  }
  else {
    return array();
  }
}



/**
 * Replaces the statically cached item for a given path.
 *
 * @param $path
 *   The path.
 * @param $router_item
 *   The router item. Usually you take a router entry from menu_get_item and
 *   set it back either modified or to a different path. This lets you modify the
 *   navigation block, the page title, the breadcrumb and the page help in one
 *   call.
 */
function menu_set_item($path, $router_item) {
  menu_get_item($path, $router_item);
}

/**
 * Get a router item.
 *
 * @param $path
 *   The path, for example node/5. The function will find the corresponding
 *   node/% item and return that.
 * @param $router_item
 *   Internal use only.
 * @return
 *   The router item, an associate array corresponding to one row in the
 *   menu_router table. The value of key map holds the loaded objects. The
 *   value of key access is TRUE if the current user can access this page.
 *   The values for key title, page_arguments, access_arguments will be
 *   filled in based on the database values and the objects loaded.
 */
function menu_get_item($path = NULL, $router_item = NULL) {
  static $router_items;
  if (!isset($path)) {
    $path = $_GET['q'];
  }
  if (isset($router_item)) {
    $router_items[$path] = $router_item;
  }
  if (!isset($router_items[$path])) {
    $original_map = arg(NULL, $path);
    $parts = array_slice($original_map, 0, MENU_MAX_PARTS);
    list($ancestors, $placeholders) = menu_get_ancestors($parts);

    if ($router_item = db_fetch_array(db_query_range('SELECT * FROM {menu_router} WHERE path IN ('. implode (',', $placeholders) .') ORDER BY fit DESC', $ancestors, 0, 1))) {
      $map = _menu_translate($router_item, $original_map);
      if ($map === FALSE) {
        $router_items[$path] = FALSE;
        return FALSE;
      }
      if ($router_item['access']) {
        $router_item['map'] = $map;
        $router_item['page_arguments'] = array_merge(menu_unserialize($router_item['page_arguments'], $map), array_slice($map, $router_item['number_parts']));
      }
    }
    $router_items[$path] = $router_item;
  }
  return $router_items[$path];
}

/**
 * Execute the page callback associated with the current path
 */
function menu_execute_active_handler($path = NULL) {
  if (_menu_site_is_offline()) {
    return MENU_SITE_OFFLINE;
  }
  // Rebuild if we know it's needed, or if the menu masks are missing which
  // occurs rarely, likely due to a race condition of multiple rebuilds.
  if (variable_get('menu_rebuild_needed', FALSE) || !variable_get('menu_masks', array())) {
    menu_rebuild();
  }
  if ($router_item = menu_get_item($path)) {
    if ($router_item['access']) {
      if ($router_item['file']) {
        require_once($router_item['file']);
      }
      return call_user_func_array($router_item['page_callback'], $router_item['page_arguments']);
    }
    else {
      return MENU_ACCESS_DENIED;
    }
  }
  return MENU_NOT_FOUND;
}

/**
 * Loads objects into the map as defined in the $item['load_functions'].
 *
 * @param $item
 *   A menu router or menu link item
 * @param $map
 *   An array of path arguments (ex: array('node', '5'))
 * @return
 *   Returns TRUE for success, FALSE if an object cannot be loaded.
 *   Names of object loading functions are placed in $item['load_functions'].
 *   Loaded objects are placed in $map[]; keys are the same as keys in the
 *   $item['load_functions'] array.
 *   $item['access'] is set to FALSE if an object cannot be loaded.
 */
function _menu_load_objects(&$item, &$map) {
  if ($load_functions = $item['load_functions']) {
    // If someone calls this function twice, then unserialize will fail.
    if ($load_functions_unserialized = unserialize($load_functions)) {
      $load_functions = $load_functions_unserialized;
    }
    $path_map = $map;
    foreach ($load_functions as $index => $function) {
      if ($function) {
        $value = isset($path_map[$index]) ? $path_map[$index] : '';
        if (is_array($function)) {
          // Set up arguments for the load function. These were pulled from
          // 'load arguments' in the hook_menu() entry, but they need
          // some processing. In this case the $function is the key to the
          // load_function array, and the value is the list of arguments.
          list($function, $args) = each($function);
          $load_functions[$index] = $function;

          // Some arguments are placeholders for dynamic items to process.
          foreach ($args as $i => $arg) {
            if ($arg === '%index') {
              // Pass on argument index to the load function, so multiple
              // occurances of the same placeholder can be identified.
              $args[$i] = $index;
            }
            if ($arg === '%map') {
              // Pass on menu map by reference. The accepting function must
              // also declare this as a reference if it wants to modify
              // the map.
              $args[$i] = &$map;
            }
            if (is_int($arg)) {
              $args[$i] = isset($path_map[$arg]) ? $path_map[$arg] : '';
            }
          }
          array_unshift($args, $value);
          $return = call_user_func_array($function, $args);
        }
        else {
          $return = $function($value);
        }
        // If callback returned an error or there is no callback, trigger 404.
        if ($return === FALSE) {
          $item['access'] = FALSE;
          $map = FALSE;
          return FALSE;
        }
        $map[$index] = $return;
      }
    }
    $item['load_functions'] = $load_functions;
  }
  return TRUE;
}

/**
 * Check access to a menu item using the access callback
 *
 * @param $item
 *   A menu router or menu link item
 * @param $map
 *   An array of path arguments (ex: array('node', '5'))
 * @return
 *   $item['access'] becomes TRUE if the item is accessible, FALSE otherwise.
 */
function _menu_check_access(&$item, $map) {
  // Determine access callback, which will decide whether or not the current
  // user has access to this path.
  $callback = empty($item['access_callback']) ? 0 : trim($item['access_callback']);
  // Check for a TRUE or FALSE value.
  if (is_numeric($callback)) {
    $item['access'] = (bool)$callback;
  }
  else {
    $arguments = menu_unserialize($item['access_arguments'], $map);
    // As call_user_func_array is quite slow and user_access is a very common
    // callback, it is worth making a special case for it.
    if ($callback == 'user_access') {
      $item['access'] = (count($arguments) == 1) ? user_access($arguments[0]) : user_access($arguments[0], $arguments[1]);
    }
    else {
      $item['access'] = call_user_func_array($callback, $arguments);
    }
  }
}

/**
 * Localize the router item title using t() or another callback.
 *
 * Translate the title and description to allow storage of English title
 * strings in the database, yet display of them in the language required
 * by the current user.
 *
 * @param $item
 *   A menu router item or a menu link item.
 * @param $map
 *   The path as an array with objects already replaced. E.g., for path
 *   node/123 $map would be array('node', $node) where $node is the node
 *   object for node 123.
 * @param $link_translate
 *   TRUE if we are translating a menu link item; FALSE if we are
 *   translating a menu router item.
 * @return
 *   No return value.
 *   $item['title'] is localized according to $item['title_callback'].
 *   If an item's callback is check_plain(), $item['options']['html'] becomes
 *   TRUE.
 *   $item['description'] is translated using t().
 *   When doing link translation and the $item['options']['attributes']['title']
 *   (link title attribute) matches the description, it is translated as well.
 */
function _menu_item_localize(&$item, $map, $link_translate = FALSE) {
  $callback = $item['title_callback'];
  $item['localized_options'] = $item['options'];
  // If we are translating the title of a menu link, and its title is the same
  // as the corresponding router item, then we can use the title information
  // from the router. If it's customized, then we need to use the link title
  // itself; can't localize.
  // If we are translating a router item (tabs, page, breadcrumb), then we
  // can always use the information from the router item.
  if (!$link_translate || ($item['title'] == $item['link_title'])) {
    // t() is a special case. Since it is used very close to all the time,
    // we handle it directly instead of using indirect, slower methods.
    if ($callback == 't') {
      if (empty($item['title_arguments'])) {
        $item['title'] = t($item['title']);
      }
      else {
        $item['title'] = t($item['title'], menu_unserialize($item['title_arguments'], $map));
      }
    }
    elseif ($callback) {
      if (empty($item['title_arguments'])) {
        $item['title'] = $callback($item['title']);
      }
      else {
        $item['title'] = call_user_func_array($callback, menu_unserialize($item['title_arguments'], $map));
      }
      // Avoid calling check_plain again on l() function.
      if ($callback == 'check_plain') {
        $item['localized_options']['html'] = TRUE;
      }
    }
  }
  elseif ($link_translate) {
    $item['title'] = $item['link_title'];
  }

  // Translate description, see the motivation above.
  if (!empty($item['description'])) {
    $original_description = $item['description'];
    $item['description'] = t($item['description']);
    if ($link_translate && isset($item['options']['attributes']['title']) && $item['options']['attributes']['title'] == $original_description) {
      $item['localized_options']['attributes']['title'] = $item['description'];
    }
  }
}

/**
 * Handles dynamic path translation and menu access control.
 *
 * When a user arrives on a page such as node/5, this function determines
 * what "5" corresponds to, by inspecting the page's menu path definition,
 * node/%node. This will call node_load(5) to load the corresponding node
 * object.
 *
 * It also works in reverse, to allow the display of tabs and menu items which
 * contain these dynamic arguments, translating node/%node to node/5.
 *
 * Translation of menu item titles and descriptions are done here to
 * allow for storage of English strings in the database, and translation
 * to the language required to generate the current page
 *
 * @param $router_item
 *   A menu router item
 * @param $map
 *   An array of path arguments (ex: array('node', '5'))
 * @param $to_arg
 *   Execute $item['to_arg_functions'] or not. Use only if you want to render a
 *   path from the menu table, for example tabs.
 * @return
 *   Returns the map with objects loaded as defined in the
 *   $item['load_functions']. $item['access'] becomes TRUE if the item is
 *   accessible, FALSE otherwise. $item['href'] is set according to the map.
 *   If an error occurs during calling the load_functions (like trying to load
 *   a non existing node) then this function return FALSE.
 */
function _menu_translate(&$router_item, $map, $to_arg = FALSE) {
  if ($to_arg) {
    // Fill in missing path elements, such as the current uid.
    _menu_link_map_translate($map, $router_item['to_arg_functions']);
  }
  // The $path_map saves the pieces of the path as strings, while elements in
  // $map may be replaced with loaded objects.
  $path_map = $map;
  if (!_menu_load_objects($router_item, $map)) {
    // An error occurred loading an object.
    $router_item['access'] = FALSE;
    return FALSE;
  }

  // Generate the link path for the page request or local tasks.
  $link_map = explode('/', $router_item['path']);
  for ($i = 0; $i < $router_item['number_parts']; $i++) {
    if ($link_map[$i] == '%') {
      $link_map[$i] = $path_map[$i];
    }
  }
  $router_item['href'] = implode('/', $link_map);
  $router_item['options'] = array();
  _menu_check_access($router_item, $map);
  
  // For performance, don't localize an item the user can't access.
  if ($router_item['access']) {
    _menu_item_localize($router_item, $map);
  }

  return $map;
}

/**
 * This function translates the path elements in the map using any to_arg
 * helper function. These functions take an argument and return an object.
 * See http://drupal.org/node/109153 for more information.
 *
 * @param map
 *   An array of path arguments (ex: array('node', '5'))
 * @param $to_arg_functions
 *   An array of helper function (ex: array(2 => 'menu_tail_to_arg'))
 */
function _menu_link_map_translate(&$map, $to_arg_functions) {
  if ($to_arg_functions) {
    $to_arg_functions = unserialize($to_arg_functions);
    foreach ($to_arg_functions as $index => $function) {
      // Translate place-holders into real values.
      $arg = $function(!empty($map[$index]) ? $map[$index] : '', $map, $index);
      if (!empty($map[$index]) || isset($arg)) {
        $map[$index] = $arg;
      }
      else {
        unset($map[$index]);
      }
    }
  }
}

function menu_tail_to_arg($arg, $map, $index) {
  return implode('/', array_slice($map, $index));
}

/**
 * This function is similar to _menu_translate() but does link-specific
 * preparation such as always calling to_arg functions.
 *
 * @param $item
 *   A menu link
 * @return
 *   Returns the map of path arguments with objects loaded as defined in the
 *   $item['load_functions']:
 *   - $item['access'] becomes TRUE if the item is accessible, FALSE otherwise.
 *   - $item['href'] is generated from link_path, possibly by to_arg functions.
 *   - $item['title'] is generated from link_title, and may be localized.
 *   - $item['options'] is unserialized; it is also changed within the call
 *     here to $item['localized_options'] by _menu_item_localize().
 */
function _menu_link_translate(&$item) {
  $item['options'] = unserialize($item['options']);
  if ($item['external']) {
    $item['access'] = 1;
    $map = array();
    $item['href'] = $item['link_path'];
    $item['title'] = $item['link_title'];
    $item['localized_options'] = $item['options'];
  }
  else {
    $map = explode('/', $item['link_path']);
    _menu_link_map_translate($map, $item['to_arg_functions']);
    $item['href'] = implode('/', $map);

    // Note - skip callbacks without real values for their arguments.
    if (strpos($item['href'], '%') !== FALSE) {
      $item['access'] = FALSE;
      return FALSE;
    }
    // menu_tree_check_access() may set this ahead of time for links to nodes.
    if (!isset($item['access'])) {
      if (!_menu_load_objects($item, $map)) {
        // An error occurred loading an object.
        $item['access'] = FALSE;
        return FALSE;
      }
      _menu_check_access($item, $map);
    }
    // For performance, don't localize a link the user can't access.
    if ($item['access']) {
      _menu_item_localize($item, $map, TRUE);
    }
  }

  // Allow other customizations - e.g. adding a page-specific query string to the
  // options array. For performance reasons we only invoke this hook if the link
  // has the 'alter' flag set in the options array.
  if (!empty($item['options']['alter'])) {
    drupal_alter('translated_menu_link', $item, $map);
  }

  return $map;
}

/**
 * Get a loaded object from a router item.
 *
 * menu_get_object() will provide you the current node on paths like node/5,
 * node/5/revisions/48 etc. menu_get_object('user') will give you the user
 * account on user/5 etc. Note - this function should never be called within a
 * _to_arg function (like user_current_to_arg()) since this may result in an
 * infinite recursion.
 *
 * @param $type
 *   Type of the object. These appear in hook_menu definitons as %type. Core
 *   provides aggregator_feed, aggregator_category, contact, filter_format,
 *   forum_term, menu, menu_link, node, taxonomy_vocabulary, user. See the
 *   relevant {$type}_load function for more on each. Defaults to node.
 * @param $position
 *   The expected position for $type object. For node/%node this is 1, for
 *   comment/reply/%node this is 2. Defaults to 1.
 * @param $path
 *   See menu_get_item() for more on this. Defaults to the current path.
 */
function menu_get_object($type = 'node', $position = 1, $path = NULL) {
  $router_item = menu_get_item($path);
  if (isset($router_item['load_functions'][$position]) && !empty($router_item['map'][$position]) && $router_item['load_functions'][$position] == $type .'_load') {
    return $router_item['map'][$position];
  }
}

/**
 * Render a menu tree based on the current path.
 *
 * The tree is expanded based on the current path and dynamic paths are also
 * changed according to the defined to_arg functions (for example the 'My account'
 * link is changed from user/% to a link with the current user's uid).
 *
 * @param $menu_name
 *   The name of the menu.
 * @return
 *   The rendered HTML of that menu on the current page.
 */
function menu_tree($menu_name = 'navigation') {
  static $menu_output = array();

  if (!isset($menu_output[$menu_name])) {
    $tree = menu_tree_page_data($menu_name);
    $menu_output[$menu_name] = menu_tree_output($tree);
  }
  return $menu_output[$menu_name];
}

/**
 * Returns a rendered menu tree.
 *
 * @param $tree
 *   A data structure representing the tree as returned from menu_tree_data.
 * @return
 *   The rendered HTML of that data structure.
 */
function menu_tree_output($tree) {
  $output = '';
  $items = array();

  // Pull out just the menu items we are going to render so that we
  // get an accurate count for the first/last classes.
  foreach ($tree as $data) {
    if (!$data['link']['hidden']) {
      $items[] = $data;
    }
  }

  $num_items = count($items);
  foreach ($items as $i => $data) {
    $extra_class = array();
    if ($i == 0) {
      $extra_class[] = 'first';
    }
    if ($i == $num_items - 1) {
      $extra_class[] = 'last';
    }
    $extra_class = implode(' ', $extra_class);
    $link = theme('menu_item_link', $data['link']);
    if ($data['below']) {
      $output .= theme('menu_item', $link, $data['link']['has_children'], menu_tree_output($data['below']), $data['link']['in_active_trail'], $extra_class);
    }
    else {
      $output .= theme('menu_item', $link, $data['link']['has_children'], '', $data['link']['in_active_trail'], $extra_class);
    }
  }
  return $output ? theme('menu_tree', $output) : '';
}

/**
 * Get the data structure representing a named menu tree.
 *
 * Since this can be the full tree including hidden items, the data returned
 * may be used for generating an an admin interface or a select.
 *
 * @param $menu_name
 *   The named menu links to return
 * @param $item
 *   A fully loaded menu link, or NULL.  If a link is supplied, only the
 *   path to root will be included in the returned tree- as if this link
 *   represented the current page in a visible menu.
 * @return
 *   An tree of menu links in an array, in the order they should be rendered.
 */
function menu_tree_all_data($menu_name = 'navigation', $item = NULL) {
  static $tree = array();

  // Use $mlid as a flag for whether the data being loaded is for the whole tree.
  $mlid = isset($item['mlid']) ? $item['mlid'] : 0;
  // Generate a cache ID (cid) specific for this $menu_name and $item.
  $cid = 'links:'. $menu_name .':all-cid:'. $mlid;

  if (!isset($tree[$cid])) {
    // If the static variable doesn't have the data, check {cache_menu}.
    $cache = cache_get($cid, 'cache_menu');
    if ($cache && isset($cache->data)) {
      // If the cache entry exists, it will just be the cid for the actual data.
      // This avoids duplication of large amounts of data.
      $cache = cache_get($cache->data, 'cache_menu');
      if ($cache && isset($cache->data)) {
        $data = $cache->data;
      }
    }
    // If the tree data was not in the cache, $data will be NULL.
    if (!isset($data)) {
      // Build and run the query, and build the tree.
      if ($mlid) {
        // The tree is for a single item, so we need to match the values in its
        // p columns and 0 (the top level) with the plid values of other links.
        $args = array(0);
        for ($i = 1; $i < MENU_MAX_DEPTH; $i++) {
          $args[] = $item["p$i"];
        }
        $args = array_unique($args);
        $placeholders = implode(', ', array_fill(0, count($args), '%d'));
        $where = ' AND ml.plid IN ('. $placeholders .')';
        $parents = $args;
        $parents[] = $item['mlid'];
      }
      else {
        // Get all links in this menu.
        $where = '';
        $args = array();
        $parents = array();
      }
      array_unshift($args, $menu_name);
      // Select the links from the table, and recursively build the tree.  We
      // LEFT JOIN since there is no match in {menu_router} for an external
      // link.
      $data['tree'] = menu_tree_data(db_query("
        SELECT m.load_functions, m.to_arg_functions, m.access_callback, m.access_arguments, m.page_callback, m.page_arguments, m.title, m.title_callback, m.title_arguments, m.type, m.description, ml.*
        FROM {menu_links} ml LEFT JOIN {menu_router} m ON m.path = ml.router_path
        WHERE ml.menu_name = '%s'". $where ."
        ORDER BY p1 ASC, p2 ASC, p3 ASC, p4 ASC, p5 ASC, p6 ASC, p7 ASC, p8 ASC, p9 ASC", $args), $parents);
      $data['node_links'] = array();
      menu_tree_collect_node_links($data['tree'], $data['node_links']);
      // Cache the data, if it is not already in the cache.
      $tree_cid = _menu_tree_cid($menu_name, $data);
      if (!cache_get($tree_cid, 'cache_menu')) {
        cache_set($tree_cid, $data, 'cache_menu');
      }
      // Cache the cid of the (shared) data using the menu and item-specific cid.
      cache_set($cid, $tree_cid, 'cache_menu');
    }
    // Check access for the current user to each item in the tree.
    menu_tree_check_access($data['tree'], $data['node_links']);
    $tree[$cid] = $data['tree'];
  }

  return $tree[$cid];
}

/**
 * Get the data structure representing a named menu tree, based on the current page.
 *
 * The tree order is maintained by storing each parent in an individual
 * field, see http://drupal.org/node/141866 for more.
 *
 * @param $menu_name
 *   The named menu links to return
 * @return
 *   An array of menu links, in the order they should be rendered. The array
 *   is a list of associative arrays -- these have two keys, link and below.
 *   link is a menu item, ready for theming as a link. Below represents the
 *   submenu below the link if there is one, and it is a subtree that has the
 *   same structure described for the top-level array.
 */
function menu_tree_page_data($menu_name = 'navigation') {
  static $tree = array();

  // Load the menu item corresponding to the current page.
  if ($item = menu_get_item()) {
    // Generate a cache ID (cid) specific for this page.
    $cid = 'links:'. $menu_name .':page-cid:'. $item['href'] .':'. (int)$item['access'];

    if (!isset($tree[$cid])) {
      // If the static variable doesn't have the data, check {cache_menu}.
      $cache = cache_get($cid, 'cache_menu');
      if ($cache && isset($cache->data)) {
        // If the cache entry exists, it will just be the cid for the actual data.
        // This avoids duplication of large amounts of data.
        $cache = cache_get($cache->data, 'cache_menu');
        if ($cache && isset($cache->data)) {
          $data = $cache->data;
        }
      }
      // If the tree data was not in the cache, $data will be NULL.
      if (!isset($data)) {
        // Build and run the query, and build the tree.
        if ($item['access']) {
          // Check whether a menu link exists that corresponds to the current path.
          $args = array($menu_name, $item['href']);
          $placeholders = "'%s'";
          if (drupal_is_front_page()) {
            $args[] = '<front>';
            $placeholders .= ", '%s'";
          }
          $parents = db_fetch_array(db_query("SELECT p1, p2, p3, p4, p5, p6, p7, p8 FROM {menu_links} WHERE menu_name = '%s' AND link_path IN (". $placeholders .")", $args));

          if (empty($parents)) {
            // If no link exists, we may be on a local task that's not in the links.
            // TODO: Handle the case like a local task on a specific node in the menu.
            $parents = db_fetch_array(db_query("SELECT p1, p2, p3, p4, p5, p6, p7, p8 FROM {menu_links} WHERE menu_name = '%s' AND link_path = '%s'", $menu_name, $item['tab_root']));
          }
          // We always want all the top-level links with plid == 0.
          $parents[] = '0';

          // Use array_values() so that the indices are numeric for array_merge().
          $args = $parents = array_unique(array_values($parents));
          $placeholders = implode(', ', array_fill(0, count($args), '%d'));
          $expanded = variable_get('menu_expanded', array());
          // Check whether the current menu has any links set to be expanded.
          if (in_array($menu_name, $expanded)) {
            // Collect all the links set to be expanded, and then add all of
            // their children to the list as well.
            do {
              $result = db_query("SELECT mlid FROM {menu_links} WHERE menu_name = '%s' AND expanded = 1 AND has_children = 1 AND plid IN (". $placeholders .') AND mlid NOT IN ('. $placeholders .')', array_merge(array($menu_name), $args, $args));
              $num_rows = FALSE;
              while ($item = db_fetch_array($result)) {
                $args[] = $item['mlid'];
                $num_rows = TRUE;
              }
              $placeholders = implode(', ', array_fill(0, count($args), '%d'));
            } while ($num_rows);
          }
          array_unshift($args, $menu_name);
        }
        else {
          // Show only the top-level menu items when access is denied.
          $args = array($menu_name, '0');
          $placeholders = '%d';
          $parents = array();
        }
        // Select the links from the table, and recursively build the tree. We
        // LEFT JOIN since there is no match in {menu_router} for an external
        // link.
        $data['tree'] = menu_tree_data(db_query("
          SELECT m.load_functions, m.to_arg_functions, m.access_callback, m.access_arguments, m.page_callback, m.page_arguments, m.title, m.title_callback, m.title_arguments, m.type, m.description, ml.*
          FROM {menu_links} ml LEFT JOIN {menu_router} m ON m.path = ml.router_path
          WHERE ml.menu_name = '%s' AND ml.plid IN (". $placeholders .")
          ORDER BY p1 ASC, p2 ASC, p3 ASC, p4 ASC, p5 ASC, p6 ASC, p7 ASC, p8 ASC, p9 ASC", $args), $parents);
        $data['node_links'] = array();
        menu_tree_collect_node_links($data['tree'], $data['node_links']);
        // Cache the data, if it is not already in the cache.
        $tree_cid = _menu_tree_cid($menu_name, $data);
        if (!cache_get($tree_cid, 'cache_menu')) {
          cache_set($tree_cid, $data, 'cache_menu');
        }
        // Cache the cid of the (shared) data using the page-specific cid.
        cache_set($cid, $tree_cid, 'cache_menu');
      }
      // Check access for the current user to each item in the tree.
      menu_tree_check_access($data['tree'], $data['node_links']);
      $tree[$cid] = $data['tree'];
    }
    return $tree[$cid];
  }

  return array();
}

/**
 * Helper function - compute the real cache ID for menu tree data.
 */
function _menu_tree_cid($menu_name, $data) {
  return 'links:'. $menu_name .':tree-data:'. md5(serialize($data));
}

/**
 * Recursive helper function - collect node links.
 *
 * @param $tree
 *   The menu tree you wish to collect node links from.
 * @param $node_links
 *   An array in which to store the collected node links.
 */
function menu_tree_collect_node_links(&$tree, &$node_links) {
  foreach ($tree as $key => $v) {
    if ($tree[$key]['link']['router_path'] == 'node/%') {
      $nid = substr($tree[$key]['link']['link_path'], 5);
      if (is_numeric($nid)) {
        $node_links[$nid][$tree[$key]['link']['mlid']] = &$tree[$key]['link'];
        $tree[$key]['link']['access'] = FALSE;
      }
    }
    if ($tree[$key]['below']) {
      menu_tree_collect_node_links($tree[$key]['below'], $node_links);
    }
  }
}

/**
 * Check access and perform other dynamic operations for each link in the tree.
 *
 * @param $tree
 *   The menu tree you wish to operate on.
 * @param $node_links
 *   A collection of node link references generated from $tree by
 *   menu_tree_collect_node_links().
 */
function menu_tree_check_access(&$tree, $node_links = array()) {

  if ($node_links) {
    // Use db_rewrite_sql to evaluate view access without loading each full node.
    $nids = array_keys($node_links);
    $placeholders = '%d'. str_repeat(', %d', count($nids) - 1);
    $result = db_query(db_rewrite_sql("SELECT n.nid FROM {node} n WHERE n.status = 1 AND n.nid IN (". $placeholders .")"), $nids);
    while ($node = db_fetch_array($result)) {
      $nid = $node['nid'];
      foreach ($node_links[$nid] as $mlid => $link) {
        $node_links[$nid][$mlid]['access'] = TRUE;
      }
    }
  }
  _menu_tree_check_access($tree);
  return;
}

/**
 * Recursive helper function for menu_tree_check_access()
 */
function _menu_tree_check_access(&$tree) {
  $new_tree = array();
  foreach ($tree as $key => $v) {
    $item = &$tree[$key]['link'];
    _menu_link_translate($item);
    if ($item['access']) {
      if ($tree[$key]['below']) {
        _menu_tree_check_access($tree[$key]['below']);
      }
      // The weights are made a uniform 5 digits by adding 50000 as an offset.
      // After _menu_link_translate(), $item['title'] has the localized link title.
      // Adding the mlid to the end of the index insures that it is unique.
      $new_tree[(50000 + $item['weight']) .' '. $item['title'] .' '. $item['mlid']] = $tree[$key];
    }
  }
  // Sort siblings in the tree based on the weights and localized titles.
  ksort($new_tree);
  $tree = $new_tree;
}

/**
 * Build the data representing a menu tree.
 *
 * @param $result
 *   The database result.
 * @param $parents
 *   An array of the plid values that represent the path from the current page
 *   to the root of the menu tree.
 * @param $depth
 *   The depth of the current menu tree.
 * @return
 *   See menu_tree_page_data for a description of the data structure.
 */
function menu_tree_data($result = NULL, $parents = array(), $depth = 1) {
  list(, $tree) = _menu_tree_data($result, $parents, $depth);
  return $tree;
}

/**
 * Recursive helper function to build the data representing a menu tree.
 *
 * The function is a bit complex because the rendering of an item depends on
 * the next menu item. So we are always rendering the element previously
 * processed not the current one.
 */
function _menu_tree_data($result, $parents, $depth, $previous_element = '') {
  $remnant = NULL;
  $tree = array();
  while ($item = db_fetch_array($result)) {
    // We need to determine if we're on the path to root so we can later build
    // the correct active trail and breadcrumb.
    $item['in_active_trail'] = in_array($item['mlid'], $parents);
    // The current item is the first in a new submenu.
    if ($item['depth'] > $depth) {
      // _menu_tree returns an item and the menu tree structure.
      list($item, $below) = _menu_tree_data($result, $parents, $item['depth'], $item);
      if ($previous_element) {
        $tree[$previous_element['mlid']] = array(
          'link' => $previous_element,
          'below' => $below,
        );
      }
      else {
        $tree = $below;
      }
      // We need to fall back one level.
      if (!isset($item) || $item['depth'] < $depth) {
        return array($item, $tree);
      }
      // This will be the link to be output in the next iteration.
      $previous_element = $item;
    }
    // We are at the same depth, so we use the previous element.
    elseif ($item['depth'] == $depth) {
      if ($previous_element) {
        // Only the first time.
        $tree[$previous_element['mlid']] = array(
          'link' => $previous_element,
          'below' => FALSE,
        );
      }
      // This will be the link to be output in the next iteration.
      $previous_element = $item;
    }
    // The submenu ended with the previous item, so pass back the current item.
    else {
      $remnant = $item;
      break;
    }
  }
  if ($previous_element) {
    // We have one more link dangling.
    $tree[$previous_element['mlid']] = array(
      'link' => $previous_element,
      'below' => FALSE,
    );
  }
  return array($remnant, $tree);
}

/**
 * Generate the HTML output for a single menu link.
 *
 * @ingroup themeable
 */
function theme_menu_item_link($link) {
  if (empty($link['localized_options'])) {
    $link['localized_options'] = array();
  }

  return l($link['title'], $link['href'], $link['localized_options']);
}

/**
 * Generate the HTML output for a menu tree
 *
 * @ingroup themeable
 */
function theme_menu_tree($tree) {
  return '<ul class="menu">'. $tree .'</ul>';
}

/**
 * Generate the HTML output for a menu item and submenu.
 *
 * @ingroup themeable
 */
function theme_menu_item($link, $has_children, $menu = '', $in_active_trail = FALSE, $extra_class = NULL) {
  $class = ($menu ? 'expanded' : ($has_children ? 'collapsed' : 'leaf'));
  if (!empty($extra_class)) {
    $class .= ' '. $extra_class;
  }
  if ($in_active_trail) {
    $class .= ' active-trail';
  }
  return '<li class="'. $class .'">'. $link . $menu ."</li>\n";
}

/**
 * Generate the HTML output for a single local task link.
 *
 * @ingroup themeable
 */
function theme_menu_local_task($link, $active = FALSE) {
  return '<li '. ($active ? 'class="active" ' : '') .'>'. $link ."</li>\n";
}

/**
 * Generates elements for the $arg array in the help hook.
 */
function drupal_help_arg($arg = array()) {
  // Note - the number of empty elements should be > MENU_MAX_PARTS.
  return $arg + array('', '', '', '', '', '', '', '', '', '', '', '');
}

/**
 * Returns the help associated with the active menu item.
 */
function menu_get_active_help() {
  $output = '';
  $router_path = menu_tab_root_path();
  // We will always have a path unless we are on a 403 or 404.
  if (!$router_path) {
    return '';
  }

  $arg = drupal_help_arg(arg(NULL));
  $empty_arg = drupal_help_arg();

  foreach (module_list() as $name) {
    if (module_hook($name, 'help')) {
      // Lookup help for this path.
      if ($help = module_invoke($name, 'help', $router_path, $arg)) {
        $output .= $help ."\n";
      }
      // Add "more help" link on admin pages if the module provides a
      // standalone help page.
      if ($arg[0] == "admin" && module_exists('help') && module_invoke($name, 'help', 'admin/help#'. $arg[2], $empty_arg) && $help) {
        $output .= theme("more_help_link", url('admin/help/'. $arg[2]));
      }
    }
  }
  return $output;
}

/**
 * Build a list of named menus.
 */
function menu_get_names($reset = FALSE) {
  static $names;

  if ($reset || empty($names)) {
    $names = array();
    $result = db_query("SELECT DISTINCT(menu_name) FROM {menu_links} ORDER BY menu_name");
    while ($name = db_fetch_array($result)) {
      $names[] = $name['menu_name'];
    }
  }
  return $names;
}

/**
 * Return an array containing the names of system-defined (default) menus.
 */
function menu_list_system_menus() {
  return array('navigation', 'primary-links', 'secondary-links');
}

/**
 * Return an array of links to be rendered as the Primary links.
 */
function menu_primary_links() {
  return menu_navigation_links(variable_get('menu_primary_links_source', 'primary-links'));
}

/**
 * Return an array of links to be rendered as the Secondary links.
 */
function menu_secondary_links() {

  // If the secondary menu source is set as the primary menu, we display the
  // second level of the primary menu.
  if (variable_get('menu_secondary_links_source', 'secondary-links') == variable_get('menu_primary_links_source', 'primary-links')) {
    return menu_navigation_links(variable_get('menu_primary_links_source', 'primary-links'), 1);
  }
  else {
    return menu_navigation_links(variable_get('menu_secondary_links_source', 'secondary-links'), 0);
  }
}

/**
 * Return an array of links for a navigation menu.
 *
 * @param $menu_name
 *   The name of the menu.
 * @param $level
 *   Optional, the depth of the menu to be returned.
 * @return
 *   An array of links of the specified menu and level.
 */
function menu_navigation_links($menu_name, $level = 0) {
  // Don't even bother querying the menu table if no menu is specified.
  if (empty($menu_name)) {
    return array();
  }

  // Get the menu hierarchy for the current page.
  $tree = menu_tree_page_data($menu_name);

  // Go down the active trail until the right level is reached.
  while ($level-- > 0 && $tree) {
    // Loop through the current level's items until we find one that is in trail.
    while ($item = array_shift($tree)) {
      if ($item['link']['in_active_trail']) {
        // If the item is in the active trail, we continue in the subtree.
        $tree = empty($item['below']) ? array() : $item['below'];
        break;
      }
    }
  }

  // Create a single level of links.
  $links = array();
  foreach ($tree as $item) {
    if (!$item['link']['hidden']) {
      $class = '';
      $l = $item['link']['localized_options'];
      $l['href'] = $item['link']['href'];
      $l['title'] = $item['link']['title'];
      if ($item['link']['in_active_trail']) {
        $class = ' active-trail';
      }
      // Keyed with the unique mlid to generate classes in theme_links().
      $links['menu-'. $item['link']['mlid'] . $class] = $l;
    }
  }
  return $links;
}

/**
 * Collects the local tasks (tabs) for a given level.
 *
 * @param $level
 *   The level of tasks you ask for. Primary tasks are 0, secondary are 1.
 * @param $return_root
 *   Whether to return the root path for the current page.
 * @return
 *   Themed output corresponding to the tabs of the requested level, or
 *   router path if $return_root == TRUE. This router path corresponds to
 *   a parent tab, if the current page is a default local task.
 */
function menu_local_tasks($level = 0, $return_root = FALSE) {
  static $tabs;
  static $root_path;

  if (!isset($tabs)) {
    $tabs = array();

    $router_item = menu_get_item();
    if (!$router_item || !$router_item['access']) {
      return '';
    }
    // Get all tabs and the root page.
    $result = db_query("SELECT * FROM {menu_router} WHERE tab_root = '%s' ORDER BY weight, title", $router_item['tab_root']);
    $map = arg();
    $children = array();
    $tasks = array();
    $root_path = $router_item['path'];

    while ($item = db_fetch_array($result)) {
      _menu_translate($item, $map, TRUE);
      if ($item['tab_parent']) {
        // All tabs, but not the root page.
        $children[$item['tab_parent']][$item['path']] = $item;
      }
      // Store the translated item for later use.
      $tasks[$item['path']] = $item;
    }

    // Find all tabs below the current path.
    $path = $router_item['path'];
    // Tab parenting may skip levels, so the number of parts in the path may not
    // equal the depth. Thus we use the $depth counter (offset by 1000 for ksort).
    $depth = 1001;
    while (isset($children[$path])) {
      $tabs_current = '';
      $next_path = '';
      $count = 0;
      foreach ($children[$path] as $item) {
        if ($item['access']) {
          $count++;
          // The default task is always active.
          if ($item['type'] == MENU_DEFAULT_LOCAL_TASK) {
            // Find the first parent which is not a default local task.
            for ($p = $item['tab_parent']; $tasks[$p]['type'] == MENU_DEFAULT_LOCAL_TASK; $p = $tasks[$p]['tab_parent']);
            $link = theme('menu_item_link', array('href' => $tasks[$p]['href']) + $item);
            $tabs_current .= theme('menu_local_task', $link, TRUE);
            $next_path = $item['path'];
          }
          else {
            $link = theme('menu_item_link', $item);
            $tabs_current .= theme('menu_local_task', $link);
          }
        }
      }
      $path = $next_path;
      $tabs[$depth]['count'] = $count;
      $tabs[$depth]['output'] = $tabs_current;
      $depth++;
    }

    // Find all tabs at the same level or above the current one.
    $parent = $router_item['tab_parent'];
    $path = $router_item['path'];
    $current = $router_item;
    $depth = 1000;
    while (isset($children[$parent])) {
      $tabs_current = '';
      $next_path = '';
      $next_parent = '';
      $count = 0;
      foreach ($children[$parent] as $item) {
        if ($item['access']) {
          $count++;
          if ($item['type'] == MENU_DEFAULT_LOCAL_TASK) {
            // Find the first parent which is not a default local task.
            for ($p = $item['tab_parent']; $tasks[$p]['type'] == MENU_DEFAULT_LOCAL_TASK; $p = $tasks[$p]['tab_parent']);
            $link = theme('menu_item_link', array('href' => $tasks[$p]['href']) + $item);
            if ($item['path'] == $router_item['path']) {
              $root_path = $tasks[$p]['path'];
            }
          }
          else {
            $link = theme('menu_item_link', $item);
          }
          // We check for the active tab.
          if ($item['path'] == $path) {
            $tabs_current .= theme('menu_local_task', $link, TRUE);
            $next_path = $item['tab_parent'];
            if (isset($tasks[$next_path])) {
              $next_parent = $tasks[$next_path]['tab_parent'];
            }
          }
          else {
            $tabs_current .= theme('menu_local_task', $link);
          }
        }
      }
      $path = $next_path;
      $parent = $next_parent;
      $tabs[$depth]['count'] = $count;
      $tabs[$depth]['output'] = $tabs_current;
      $depth--;
    }
    // Sort by depth.
    ksort($tabs);
    // Remove the depth, we are interested only in their relative placement.
    $tabs = array_values($tabs);
  }

  if ($return_root) {
    return $root_path;
  }
  else {
    // We do not display single tabs.
    return (isset($tabs[$level]) && $tabs[$level]['count'] > 1) ? $tabs[$level]['output'] : '';
  }
}

/**
 * Returns the rendered local tasks at the top level.
 */
function menu_primary_local_tasks() {
  return menu_local_tasks(0);
}

/**
 * Returns the rendered local tasks at the second level.
 */
function menu_secondary_local_tasks() {
  return menu_local_tasks(1);
}

/**
 * Returns the router path, or the path of the parent tab of a default local task.
 */
function menu_tab_root_path() {
  return menu_local_tasks(0, TRUE);
}

/**
 * Returns the rendered local tasks. The default implementation renders them as tabs.
 *
 * @ingroup themeable
 */
function theme_menu_local_tasks() {
  $output = '';

  if ($primary = menu_primary_local_tasks()) {
    $output .= "<ul class=\"tabs primary\">\n". $primary ."</ul>\n";
  }
  if ($secondary = menu_secondary_local_tasks()) {
    $output .= "<ul class=\"tabs secondary\">\n". $secondary ."</ul>\n";
  }

  return $output;
}

/**
 * Set (or get) the active menu for the current page - determines the active trail.
 */
function menu_set_active_menu_name($menu_name = NULL) {
  static $active;

  if (isset($menu_name)) {
    $active = $menu_name;
  }
  elseif (!isset($active)) {
    $active = 'navigation';
  }
  return $active;
}

/**
 * Get the active menu for the current page - determines the active trail.
 */
function menu_get_active_menu_name() {
  return menu_set_active_menu_name();
}

/**
 * Set the active path, which determines which page is loaded.
 *
 * @param $path
 *   A Drupal path - not a path alias.
 *
 * Note that this may not have the desired effect unless invoked very early
 * in the page load, such as during hook_boot, or unless you call
 * menu_execute_active_handler() to generate your page output.
 */
function menu_set_active_item($path) {
  $_GET['q'] = $path;
}

/**
 * Sets or gets the active trail (path to root menu root) of the current page.
 *
 * @param $new_trail
 *   Menu trail to set, or NULL to use previously-set or calculated trail. If
 *   supplying a trail, use the same format as the return value (see below).
 *
 * @return
 *   Path to menu root of the current page, as an array of menu link items,
 *   starting with the site's home page. Each link item is an associative array
 *   with the following components:
 *   - title: Title of the item.
 *   - href: Drupal path of the item.
 *   - localized_options: Options for passing into the l() function.
 *   - type: A menu type constant, such as MENU_DEFAULT_LOCAL_TASK, or 0 to
 *     indicate it's not really in the menu (used for the home page item).
 *   If $new_trail is supplied, the value is saved in a static variable and
 *   returned. If $new_trail is not supplied, and there is a saved value from
 *   a previous call, the saved value is returned. If $new_trail is not supplied
 *   and there is no saved value, the path to the current page is calculated,
 *   saved as the static value, and returned.
 */
function menu_set_active_trail($new_trail = NULL) {
  static $trail;

  if (isset($new_trail)) {
    $trail = $new_trail;
  }
  elseif (!isset($trail)) {
    $trail = array();
    $trail[] = array('title' => t('Home'), 'href' => '<front>', 'localized_options' => array(), 'type' => 0);
    $item = menu_get_item();

    // Check whether the current item is a local task (displayed as a tab).
    if ($item['tab_parent']) {
      // The title of a local task is used for the tab, never the page title.
      // Thus, replace it with the item corresponding to the root path to get
      // the relevant href and title.  For example, the menu item corresponding
      // to 'admin' is used when on the 'By module' tab at 'admin/by-module'.
      $parts = explode('/', $item['tab_root']);
      $args = arg();
      // Replace wildcards in the root path using the current path.
      foreach ($parts as $index => $part) {
        if ($part == '%') {
          $parts[$index] = $args[$index];
        }
      }
      // Retrieve the menu item using the root path after wildcard replacement.
      $root_item = menu_get_item(implode('/', $parts));
      if ($root_item && $root_item['access']) {
        $item = $root_item;
      }
    }

    $tree = menu_tree_page_data(menu_get_active_menu_name());
    list($key, $curr) = each($tree);

    while ($curr) {
      // Terminate the loop when we find the current path in the active trail.
      if ($curr['link']['href'] == $item['href']) {
        $trail[] = $curr['link'];
        $curr = FALSE;
      }
      else {
        // Add the link if it's in the active trail, then move to the link below.
        if ($curr['link']['in_active_trail']) {
          $trail[] = $curr['link'];
          $tree = $curr['below'] ? $curr['below'] : array();
        }
        list($key, $curr) = each($tree);
      }
    }
    // Make sure the current page is in the trail (needed for the page title),
    // but exclude tabs and the front page.
    $last = count($trail) - 1;
    if ($trail[$last]['href'] != $item['href'] && !(bool)($item['type'] & MENU_IS_LOCAL_TASK) && !drupal_is_front_page()) {
      $trail[] = $item;
    }
  }
  return $trail;
}

/**
 * Gets the active trail (path to root menu root) of the current page.
 *
 * See menu_set_active_trail() for details of return value.
 */
function menu_get_active_trail() {
  return menu_set_active_trail();
}

/**
 * Get the breadcrumb for the current page, as determined by the active trail.
 */
function menu_get_active_breadcrumb() {
  $breadcrumb = array();

  // No breadcrumb for the front page.
  if (drupal_is_front_page()) {
    return $breadcrumb;
  }

  $item = menu_get_item();
  if ($item && $item['access']) {
    $active_trail = menu_get_active_trail();

    foreach ($active_trail as $parent) {
      $breadcrumb[] = l($parent['title'], $parent['href'], $parent['localized_options']);
    }
    $end = end($active_trail);

    // Don't show a link to the current page in the breadcrumb trail.
    if ($item['href'] == $end['href'] || ($item['type'] == MENU_DEFAULT_LOCAL_TASK && $end['href'] != '<front>')) {
      array_pop($breadcrumb);
    }
  }
  return $breadcrumb;
}

/**
 * Get the title of the current page, as determined by the active trail.
 */
function menu_get_active_title() {
  $active_trail = menu_get_active_trail();

  foreach (array_reverse($active_trail) as $item) {
    if (!(bool)($item['type'] & MENU_IS_LOCAL_TASK)) {
      return $item['title'];
    }
  }
}

/**
 * Get a menu link by its mlid, access checked and link translated for rendering.
 *
 * This function should never be called from within node_load() or any other
 * function used as a menu object load function since an infinite recursion may
 * occur.
 *
 * @param $mlid
 *   The mlid of the menu item.
 * @return
 *   A menu link, with $item['access'] filled and link translated for
 *   rendering.
 */
function menu_link_load($mlid) {
  if (is_numeric($mlid) && $item = db_fetch_array(db_query("SELECT m.*, ml.* FROM {menu_links} ml LEFT JOIN {menu_router} m ON m.path = ml.router_path WHERE ml.mlid = %d", $mlid))) {
    _menu_link_translate($item);
    return $item;
  }
  return FALSE;
}

/**
 * Clears the cached cached data for a single named menu.
 */
function menu_cache_clear($menu_name = 'navigation') {
  static $cache_cleared = array();

  if (empty($cache_cleared[$menu_name])) {
    cache_clear_all('links:'. $menu_name .':', 'cache_menu', TRUE);
    $cache_cleared[$menu_name] = 1;
  }
  elseif ($cache_cleared[$menu_name] == 1) {
    register_shutdown_function('cache_clear_all', 'links:'. $menu_name .':', 'cache_menu', TRUE);
    $cache_cleared[$menu_name] = 2;
  }
}

/**
 * Clears all cached menu data.  This should be called any time broad changes
 * might have been made to the router items or menu links.
 */
function menu_cache_clear_all() {
  cache_clear_all('*', 'cache_menu', TRUE);
}

/**
 * (Re)populate the database tables used by various menu functions.
 *
 * This function will clear and populate the {menu_router} table, add entries
 * to {menu_links} for new router items, then remove stale items from
 * {menu_links}. If called from update.php or install.php, it will also
 * schedule a call to itself on the first real page load from
 * menu_execute_active_handler(), because the maintenance page environment
 * is different and leaves stale data in the menu tables.
 */
function menu_rebuild() {
  if (!lock_acquire('menu_rebuild')) {
    // Wait for another request that is already doing this work.
    // We choose to block here since otherwise the router item may not 
    // be avaiable in menu_execute_active_handler() resulting in a 404.
    lock_wait('menu_rebuild');
    return FALSE;
  }

  $menu = menu_router_build(TRUE);
  _menu_navigation_links_rebuild($menu);
  // Clear the menu, page and block caches.
  menu_cache_clear_all();
  _menu_clear_page_cache();
  
  if (defined('MAINTENANCE_MODE')) {
    variable_set('menu_rebuild_needed', TRUE);
  }
  else {
    variable_del('menu_rebuild_needed');
  }
  lock_release('menu_rebuild');
  return TRUE;
}

/**
 * Collect, alter and store the menu definitions.
 */
function menu_router_build($reset = FALSE) {
  static $menu;

  if (!isset($menu) || $reset) {
    // We need to manually call each module so that we can know which module
    // a given item came from.
    $callbacks = array();
    foreach (module_implements('menu') as $module) {
      $router_items = call_user_func($module .'_menu');
      if (isset($router_items) && is_array($router_items)) {
        foreach (array_keys($router_items) as $path) {
          $router_items[$path]['module'] = $module;
        }
        $callbacks = array_merge($callbacks, $router_items);
      }
    }
    // Alter the menu as defined in modules, keys are like user/%user.
    drupal_alter('menu', $callbacks);
    $menu = _menu_router_build($callbacks);
    _menu_router_cache($menu);
  }
  return $menu;
}

/**
 * Helper function to store the menu router if we have it in memory.
 */
function _menu_router_cache($new_menu = NULL) {
  static $menu = NULL;

  if (isset($new_menu)) {
    $menu = $new_menu;
  }
  return $menu;
}

/**
 * Builds a link from a router item.
 */
function _menu_link_build($item) {
  if ($item['type'] == MENU_CALLBACK) {
    $item['hidden'] = -1;
  }
  elseif ($item['type'] == MENU_SUGGESTED_ITEM) {
    $item['hidden'] = 1;
  }
  // Note, we set this as 'system', so that we can be sure to distinguish all
  // the menu links generated automatically from entries in {menu_router}.
  $item['module'] = 'system';
  $item += array(
    'menu_name' => 'navigation',
    'link_title' => $item['title'],
    'link_path' => $item['path'],
    'hidden' => 0,
    'options' => empty($item['description']) ? array() : array('attributes' => array('title' => $item['description'])),
  );
  return $item;
}

/**
 * Helper function to build menu links for the items in the menu router.
 */
function _menu_navigation_links_rebuild($menu) {
  // Add normal and suggested items as links.
  $menu_links = array();
  foreach ($menu as $path => $item) {
    if ($item['_visible']) {
      $item = _menu_link_build($item);
      $menu_links[$path] = $item;
      $sort[$path] = $item['_number_parts'];
    }
  }
  if ($menu_links) {
    // Make sure no child comes before its parent.
    array_multisort($sort, SORT_NUMERIC, $menu_links);

    foreach ($menu_links as $item) {
      $existing_item = db_fetch_array(db_query("SELECT mlid, menu_name, plid, customized, has_children, updated FROM {menu_links} WHERE link_path = '%s' AND module = '%s'", $item['link_path'], 'system'));
      if ($existing_item) {
        $item['mlid'] = $existing_item['mlid'];
        // A change in hook_menu may move the link to a different menu
        if (empty($item['menu_name']) || ($item['menu_name'] == $existing_item['menu_name'])) {
          $item['menu_name'] = $existing_item['menu_name'];
          $item['plid'] = $existing_item['plid'];
        }
        $item['has_children'] = $existing_item['has_children'];
        $item['updated'] = $existing_item['updated'];
      }
      if (!$existing_item || !$existing_item['customized']) {
        menu_link_save($item);
      }
    }
  }
  $placeholders = db_placeholders($menu, 'varchar');
  $paths = array_keys($menu);
  // Updated and customized items whose router paths are gone need new ones.
  $result = db_query("SELECT ml.link_path, ml.mlid, ml.router_path, ml.updated FROM {menu_links} ml WHERE ml.updated = 1 OR (router_path NOT IN ($placeholders) AND external = 0 AND customized = 1)", $paths);
  while ($item = db_fetch_array($result)) {
    $router_path = _menu_find_router_path($item['link_path']);
    if (!empty($router_path) && ($router_path != $item['router_path'] || $item['updated'])) {
      // If the router path and the link path matches, it's surely a working
      // item, so we clear the updated flag.
      $updated = $item['updated'] && $router_path != $item['link_path'];
      db_query("UPDATE {menu_links} SET router_path = '%s', updated = %d WHERE mlid = %d", $router_path, $updated, $item['mlid']);
    }
  }
  // Find any item whose router path does not exist any more.
  $result = db_query("SELECT * FROM {menu_links} WHERE router_path NOT IN ($placeholders) AND external = 0 AND updated = 0 AND customized = 0 ORDER BY depth DESC", $paths);
  // Remove all such items. Starting from those with the greatest depth will
  // minimize the amount of re-parenting done by menu_link_delete().
  while ($item = db_fetch_array($result)) {
    _menu_delete_item($item, TRUE);
  }
}

/**
 * Delete one or several menu links.
 *
 * @param $mlid
 *   A valid menu link mlid or NULL. If NULL, $path is used.
 * @param $path
 *   The path to the menu items to be deleted. $mlid must be NULL.
 */
function menu_link_delete($mlid, $path = NULL) {
  if (isset($mlid)) {
    _menu_delete_item(db_fetch_array(db_query("SELECT * FROM {menu_links} WHERE mlid = %d", $mlid)));
  }
  else {
    $result = db_query("SELECT * FROM {menu_links} WHERE link_path = '%s'", $path);
    while ($link = db_fetch_array($result)) {
      _menu_delete_item($link);
    }
  }
}

/**
 * Helper function for menu_link_delete; deletes a single menu link.
 *
 * @param $item
 *   Item to be deleted.
 * @param $force
 *   Forces deletion. Internal use only, setting to TRUE is discouraged.
 */
function _menu_delete_item($item, $force = FALSE) {
  if ($item && ($item['module'] != 'system' || $item['updated'] || $force)) {
    // Children get re-attached to the item's parent.
    if ($item['has_children']) {
      $result = db_query("SELECT mlid FROM {menu_links} WHERE plid = %d", $item['mlid']);
      while ($m = db_fetch_array($result)) {
        $child = menu_link_load($m['mlid']);
        $child['plid'] = $item['plid'];
        menu_link_save($child);
      }
    }
    db_query('DELETE FROM {menu_links} WHERE mlid = %d', $item['mlid']);

    // Update the has_children status of the parent.
    _menu_update_parental_status($item);
    menu_cache_clear($item['menu_name']);
    _menu_clear_page_cache();
  }
}

/**
 * Save a menu link.
 *
 * @param $item
 *   An array representing a menu link item. The only mandatory keys are
 *   link_path and link_title. Possible keys are:
 *   - menu_name: Default is navigation.
 *   - weight: Default is 0.
 *   - expanded: Whether the item is expanded.
 *   - options: An array of options, see l() for more.
 *   - mlid: Set to an existing value, or 0 or NULL to insert a new link.
 *   - plid: The mlid of the parent.
 *   - router_path: The path of the relevant router item.
 *
 * @return
 *   The mlid of the saved menu link, or FALSE if the menu link could not be 
 *   saved.
 */
function menu_link_save(&$item) {

  // Get the router if it's already in memory. $menu will be NULL, unless this
  // is during a menu rebuild
  $menu = _menu_router_cache();
  drupal_alter('menu_link', $item, $menu);

  // This is the easiest way to handle the unique internal path '<front>',
  // since a path marked as external does not need to match a router path.
  $item['_external'] = menu_path_is_external($item['link_path'])  || $item['link_path'] == '<front>';
  // Load defaults.
  $item += array(
    'menu_name' => 'navigation',
    'weight' => 0,
    'link_title' => '',
    'hidden' => 0,
    'has_children' => 0,
    'expanded' => 0,
    'options' => array(),
    'module' => 'menu',
    'customized' => 0,
    'updated' => 0,
  );
  $existing_item = FALSE;
  if (isset($item['mlid'])) {
    $existing_item = db_fetch_array(db_query("SELECT * FROM {menu_links} WHERE mlid = %d", $item['mlid']));
  }

  if (isset($item['plid'])) {
    $parent = db_fetch_array(db_query("SELECT * FROM {menu_links} WHERE mlid = %d", $item['plid']));
  }
  else {
    // Find the parent - it must be unique.
    $parent_path = $item['link_path'];
    $where = "WHERE link_path = '%s'";
    // Only links derived from router items should have module == 'system', and
    // we want to find the parent even if it's in a different menu.
    if ($item['module'] == 'system') {
      $where .= " AND module = '%s'";
      $arg2 = 'system';
    }
    else {
      // If not derived from a router item, we respect the specified menu name.
      $where .= " AND menu_name = '%s'";
      $arg2 = $item['menu_name'];
    }
    do {
      $parent = FALSE;
      $parent_path = substr($parent_path, 0, strrpos($parent_path, '/'));
      $result = db_query("SELECT COUNT(*) FROM {menu_links} ". $where, $parent_path, $arg2);
      // Only valid if we get a unique result.
      if (db_result($result) == 1) {
        $parent = db_fetch_array(db_query("SELECT * FROM {menu_links} ". $where, $parent_path, $arg2));
      }
    } while ($parent === FALSE && $parent_path);
  }
  if ($parent !== FALSE) {
    $item['menu_name'] = $parent['menu_name'];
  }
  $menu_name = $item['menu_name'];
  // Menu callbacks need to be in the links table for breadcrumbs, but can't
  // be parents if they are generated directly from a router item.
  if (empty($parent['mlid']) || $parent['hidden'] < 0) {
    $item['plid'] =  0;
  }
  else {
    $item['plid'] = $parent['mlid'];
  }

  if (!$existing_item) {
    db_query("INSERT INTO {menu_links} (
       menu_name, plid, link_path,
      hidden, external, has_children,
      expanded, weight,
      module, link_title, options,
      customized, updated) VALUES (
      '%s', %d, '%s',
      %d, %d, %d,
      %d, %d,
      '%s', '%s', '%s', %d, %d)",
      $item['menu_name'], $item['plid'], $item['link_path'],
      $item['hidden'], $item['_external'], $item['has_children'],
      $item['expanded'], $item['weight'],
      $item['module'],  $item['link_title'], serialize($item['options']),
      $item['customized'], $item['updated']);
    $item['mlid'] = db_last_insert_id('menu_links', 'mlid');
  }

  if (!$item['plid']) {
    $item['p1'] = $item['mlid'];
    for ($i = 2; $i <= MENU_MAX_DEPTH; $i++) {
      $item["p$i"] = 0;
    }
    $item['depth'] = 1;
  }
  else {
    // Cannot add beyond the maximum depth.
    if ($item['has_children'] && $existing_item) {
      $limit = MENU_MAX_DEPTH - menu_link_children_relative_depth($existing_item) - 1;
    }
    else {
      $limit = MENU_MAX_DEPTH - 1;
    }
    if ($parent['depth'] > $limit) {
      return FALSE;
    }
    $item['depth'] = $parent['depth'] + 1;
    _menu_link_parents_set($item, $parent);
  }
  // Need to check both plid and menu_name, since plid can be 0 in any menu.
  if ($existing_item && ($item['plid'] != $existing_item['plid'] || $menu_name != $existing_item['menu_name'])) {
    _menu_link_move_children($item, $existing_item);
  }
  // Find the callback. During the menu update we store empty paths to be
  // fixed later, so we skip this.
  if (!isset($_SESSION['system_update_6021']) && (empty($item['router_path'])  || !$existing_item || ($existing_item['link_path'] != $item['link_path']))) {
    if ($item['_external']) {
      $item['router_path'] = '';
    }
    else {
      // Find the router path which will serve this path.
      $item['parts'] = explode('/', $item['link_path'], MENU_MAX_PARTS);
      $item['router_path'] = _menu_find_router_path($item['link_path']);
    }
  }
  db_query("UPDATE {menu_links} SET menu_name = '%s', plid = %d, link_path = '%s',
    router_path = '%s', hidden = %d, external = %d, has_children = %d,
    expanded = %d, weight = %d, depth = %d,
    p1 = %d, p2 = %d, p3 = %d, p4 = %d, p5 = %d, p6 = %d, p7 = %d, p8 = %d, p9 = %d,
    module = '%s', link_title = '%s', options = '%s', customized = %d WHERE mlid = %d",
    $item['menu_name'], $item['plid'], $item['link_path'],
    $item['router_path'], $item['hidden'], $item['_external'], $item['has_children'],
    $item['expanded'], $item['weight'],  $item['depth'],
    $item['p1'], $item['p2'], $item['p3'], $item['p4'], $item['p5'], $item['p6'], $item['p7'], $item['p8'], $item['p9'],
    $item['module'],  $item['link_title'], serialize($item['options']), $item['customized'], $item['mlid']);
  // Check the has_children status of the parent.
  _menu_update_parental_status($item);
  menu_cache_clear($menu_name);
  if ($existing_item && $menu_name != $existing_item['menu_name']) {
    menu_cache_clear($existing_item['menu_name']);
  }

  _menu_clear_page_cache();
  return $item['mlid'];
}

/**
 * Helper function to clear the page and block caches at most twice per page load.
 */
function _menu_clear_page_cache() {
  static $cache_cleared = 0;

  // Clear the page and block caches, but at most twice, including at
  //  the end of the page load when there are multple links saved or deleted.
  if (empty($cache_cleared)) {
    cache_clear_all();
    // Keep track of which menus have expanded items.
    _menu_set_expanded_menus();
    $cache_cleared = 1;
  }
  elseif ($cache_cleared == 1) {
    register_shutdown_function('cache_clear_all');
    // Keep track of which menus have expanded items.
    register_shutdown_function('_menu_set_expanded_menus');
    $cache_cleared = 2;
  }
}

/**
 * Helper function to update a list of menus with expanded items
 */
function _menu_set_expanded_menus() {
  $names = array();
  $result = db_query("SELECT menu_name FROM {menu_links} WHERE expanded != 0 GROUP BY menu_name");
  while ($n = db_fetch_array($result)) {
    $names[] = $n['menu_name'];
  }
  variable_set('menu_expanded', $names);
}

/**
 * Find the router path which will serve this path.
 *
 * @param $link_path
 *  The path for we are looking up its router path.
 * @return
 *  A path from $menu keys or empty if $link_path points to a nonexisting
 *  place.
 */
function _menu_find_router_path($link_path) {
  // $menu will only have data during a menu rebuild.
  $menu = _menu_router_cache();

  $router_path = $link_path;
  $parts = explode('/', $link_path, MENU_MAX_PARTS);
  list($ancestors, $placeholders) = menu_get_ancestors($parts);

  if (empty($menu)) {
    // Not during a menu rebuild, so look up in the database.
    $router_path = (string)db_result(db_query_range('SELECT path FROM {menu_router} WHERE path IN ('. implode (',', $placeholders) .') ORDER BY fit DESC', $ancestors, 0, 1));
  }
  elseif (!isset($menu[$router_path])) {
    // Add an empty path as a fallback.
    $ancestors[] = '';
    foreach ($ancestors as $key => $router_path) {
      if (isset($menu[$router_path])) {
        // Exit the loop leaving $router_path as the first match.
        break;
      }
    }
    // If we did not find the path, $router_path will be the empty string
    // at the end of $ancestors.
  }
  return $router_path;
}

/**
 * Insert, update or delete an uncustomized menu link related to a module.
 *
 * @param $module
 *   The name of the module.
 * @param $op
 *   Operation to perform: insert, update or delete.
 * @param $link_path
 *   The path this link points to.
 * @param $link_title
 *   Title of the link to insert or new title to update the link to.
 *   Unused for delete.
 * @return
 *   The insert op returns the mlid of the new item. Others op return NULL.
 */
function menu_link_maintain($module, $op, $link_path, $link_title) {
  switch ($op) {
    case 'insert':
      $menu_link = array(
        'link_title' => $link_title,
        'link_path' => $link_path,
        'module' => $module,
      );
      return menu_link_save($menu_link);
      break;
    case 'update':
      db_query("UPDATE {menu_links} SET link_title = '%s' WHERE link_path = '%s' AND customized = 0 AND module = '%s'", $link_title, $link_path, $module);
      $result = db_query("SELECT menu_name FROM {menu_links} WHERE link_path = '%s' AND customized = 0 AND module = '%s'", $link_path, $module);
      while ($item = db_fetch_array($result)) {
        menu_cache_clear($item['menu_name']);
      }
      break;
    case 'delete':
      menu_link_delete(NULL, $link_path);
      break;
  }
}

/**
 * Find the depth of an item's children relative to its depth.
 *
 * For example, if the item has a depth of 2, and the maximum of any child in
 * the menu link tree is 5, the relative depth is 3.
 *
 * @param $item
 *   An array representing a menu link item.
 * @return
 *   The relative depth, or zero.
 *
 */
function menu_link_children_relative_depth($item) {
  $i = 1;
  $match = '';
  $args[] = $item['menu_name'];
  $p = 'p1';
  while ($i <= MENU_MAX_DEPTH && $item[$p]) {
    $match .= " AND $p = %d";
    $args[] = $item[$p];
    $p = 'p'. ++$i;
  }

  $max_depth = db_result(db_query_range("SELECT depth FROM {menu_links} WHERE menu_name = '%s'". $match ." ORDER BY depth DESC", $args, 0, 1));

  return ($max_depth > $item['depth']) ? $max_depth - $item['depth'] : 0;
}

/**
 * Update the children of a menu link that's being moved.
 *
 * The menu name, parents (p1 - p6), and depth are updated for all children of
 * the link, and the has_children status of the previous parent is updated.
 */
function _menu_link_move_children($item, $existing_item) {

  $args[] = $item['menu_name'];
  $set[] = "menu_name = '%s'";

  $i = 1;
  while ($i <= $item['depth']) {
    $p = 'p'. $i++;
    $set[] = "$p = %d";
    $args[] = $item[$p];
  }
  $j = $existing_item['depth'] + 1;
  while ($i <= MENU_MAX_DEPTH && $j <= MENU_MAX_DEPTH) {
    $set[] = 'p'. $i++ .' = p'. $j++;
  }
  while ($i <= MENU_MAX_DEPTH) {
    $set[] = 'p'. $i++ .' = 0';
  }

  $shift = $item['depth'] - $existing_item['depth'];
  if ($shift < 0) {
    $args[] = -$shift;
    $set[] = 'depth = depth - %d';
  }
  elseif ($shift > 0) {
    // The order of $set must be reversed so the new values don't overwrite the
    // old ones before they can be used because "Single-table UPDATE
    // assignments are generally evaluated from left to right"
    // see: http://dev.mysql.com/doc/refman/5.0/en/update.html
    $set = array_reverse($set);
    $args = array_reverse($args);

    $args[] = $shift;
    $set[] = 'depth = depth + %d';
  }
  $where[] = "menu_name = '%s'";
  $args[] = $existing_item['menu_name'];
  $p = 'p1';
  for ($i = 1; $i <= MENU_MAX_DEPTH && $existing_item[$p]; $p = 'p'. ++$i) {
    $where[] = "$p = %d";
    $args[] = $existing_item[$p];
  }

  db_query("UPDATE {menu_links} SET ". implode(', ', $set) ." WHERE ". implode(' AND ', $where), $args);
  // Check the has_children status of the parent, while excluding this item.
  _menu_update_parental_status($existing_item, TRUE);
}

/**
 * Check and update the has_children status for the parent of a link.
 */
function _menu_update_parental_status($item, $exclude = FALSE) {
  // If plid == 0, there is nothing to update.
  if ($item['plid']) {
    // We may want to exclude the passed link as a possible child.
    $where = $exclude ? " AND mlid != %d" : '';
    // Check if at least one visible child exists in the table.
    $parent_has_children = (bool)db_result(db_query_range("SELECT mlid FROM {menu_links} WHERE menu_name = '%s' AND plid = %d AND hidden = 0". $where, $item['menu_name'], $item['plid'], $item['mlid'], 0, 1));
    db_query("UPDATE {menu_links} SET has_children = %d WHERE mlid = %d", $parent_has_children, $item['plid']);
  }
}

/**
 * Helper function that sets the p1..p9 values for a menu link being saved.
 */
function _menu_link_parents_set(&$item, $parent) {
  $i = 1;
  while ($i < $item['depth']) {
    $p = 'p'. $i++;
    $item[$p] = $parent[$p];
  }
  $p = 'p'. $i++;
  // The parent (p1 - p9) corresponding to the depth always equals the mlid.
  $item[$p] = $item['mlid'];
  while ($i <= MENU_MAX_DEPTH) {
    $p = 'p'. $i++;
    $item[$p] = 0;
  }
}

/**
 * Helper function to build the router table based on the data from hook_menu.
 */
function _menu_router_build($callbacks) {
  // First pass: separate callbacks from paths, making paths ready for
  // matching. Calculate fitness, and fill some default values.
  $menu = array();
  foreach ($callbacks as $path => $item) {
    $load_functions = array();
    $to_arg_functions = array();
    $fit = 0;
    $move = FALSE;

    $parts = explode('/', $path, MENU_MAX_PARTS);
    $number_parts = count($parts);
    // We store the highest index of parts here to save some work in the fit
    // calculation loop.
    $slashes = $number_parts - 1;
    // Extract load and to_arg functions.
    foreach ($parts as $k => $part) {
      $match = FALSE;
      // Look for wildcards in the form allowed to be used in PHP functions,
      // because we are using these to construct the load function names.
      // See http://php.net/manual/en/language.functions.php for reference.
      if (preg_match('/^%(|[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$/', $part, $matches)) {
        if (empty($matches[1])) {
          $match = TRUE;
          $load_functions[$k] = NULL;
        }
        else {
          if (function_exists($matches[1] .'_to_arg')) {
            $to_arg_functions[$k] = $matches[1] .'_to_arg';
            $load_functions[$k] = NULL;
            $match = TRUE;
          }
          if (function_exists($matches[1] .'_load')) {
            $function = $matches[1] .'_load';
            // Create an array of arguments that will be passed to the _load
            // function when this menu path is checked, if 'load arguments'
            // exists.
            $load_functions[$k] = isset($item['load arguments']) ? array($function => $item['load arguments']) : $function;
            $match = TRUE;
          }
        }
      }
      if ($match) {
        $parts[$k] = '%';
      }
      else {
        $fit |=  1 << ($slashes - $k);
      }
    }
    if ($fit) {
      $move = TRUE;
    }
    else {
      // If there is no %, it fits maximally.
      $fit = (1 << $number_parts) - 1;
    }
    $masks[$fit] = 1;
    $item['load_functions'] = empty($load_functions) ? '' : serialize($load_functions);
    $item['to_arg_functions'] = empty($to_arg_functions) ? '' : serialize($to_arg_functions);
    $item += array(
      'title' => '',
      'weight' => 0,
      'type' => MENU_NORMAL_ITEM,
      '_number_parts' => $number_parts,
      '_parts' => $parts,
      '_fit' => $fit,
    );
    $item += array(
      '_visible' => (bool)($item['type'] & MENU_VISIBLE_IN_BREADCRUMB),
      '_tab' => (bool)($item['type'] & MENU_IS_LOCAL_TASK),
    );
    if ($move) {
      $new_path = implode('/', $item['_parts']);
      $menu[$new_path] = $item;
      $sort[$new_path] = $number_parts;
    }
    else {
      $menu[$path] = $item;
      $sort[$path] = $number_parts;
    }
  }
  array_multisort($sort, SORT_NUMERIC, $menu);

  if (!$menu) {
    // We must have a serious error - there is no data to save.
    watchdog('php', 'Menu router rebuild failed - some paths may not work correctly.', array(), WATCHDOG_ERROR);
    return array();
  }
  // Delete the existing router since we have some data to replace it.
  db_query('DELETE FROM {menu_router}');
  // Apply inheritance rules.
  foreach ($menu as $path => $v) {
    $item = &$menu[$path];
    if (!$item['_tab']) {
      // Non-tab items.
      $item['tab_parent'] = '';
      $item['tab_root'] = $path;
    }
    for ($i = $item['_number_parts'] - 1; $i; $i--) {
      $parent_path = implode('/', array_slice($item['_parts'], 0, $i));
      if (isset($menu[$parent_path])) {

        $parent = $menu[$parent_path];

        if (!isset($item['tab_parent'])) {
          // Parent stores the parent of the path.
          $item['tab_parent'] = $parent_path;
        }
        if (!isset($item['tab_root']) && !$parent['_tab']) {
          $item['tab_root'] = $parent_path;
        }
        // If an access callback is not found for a default local task we use
        // the callback from the parent, since we expect them to be identical.
        // In all other cases, the access parameters must be specified.
        if (($item['type'] == MENU_DEFAULT_LOCAL_TASK) && !isset($item['access callback']) && isset($parent['access callback'])) {
          $item['access callback'] = $parent['access callback'];
          if (!isset($item['access arguments']) && isset($parent['access arguments'])) {
            $item['access arguments'] = $parent['access arguments'];
          }
        }
        // Same for page callbacks.
        if (!isset($item['page callback']) && isset($parent['page callback'])) {
          $item['page callback'] = $parent['page callback'];
          if (!isset($item['page arguments']) && isset($parent['page arguments'])) {
            $item['page arguments'] = $parent['page arguments'];
          }
          if (!isset($item['file']) && isset($parent['file'])) {
            $item['file'] = $parent['file'];
          }
          if (!isset($item['file path']) && isset($parent['file path'])) {
            $item['file path'] = $parent['file path'];
          }
        }
      }
    }
    if (!isset($item['access callback']) && isset($item['access arguments'])) {
      // Default callback.
      $item['access callback'] = 'user_access';
    }
    if (!isset($item['access callback']) || empty($item['page callback'])) {
      $item['access callback'] = 0;
    }
    if (is_bool($item['access callback'])) {
      $item['access callback'] = intval($item['access callback']);
    }

    $item += array(
      'access arguments' => array(),
      'access callback' => '',
      'page arguments' => array(),
      'page callback' => '',
      'block callback' => '',
      'title arguments' => array(),
      'title callback' => 't',
      'description' => '',
      'position' => '',
      'tab_parent' => '',
      'tab_root' => $path,
      'path' => $path,
      'file' => '',
      'file path' => '',
      'include file' => '',
      'module' => '',
    );

    // Calculate out the file to be included for each callback, if any.
    if ($item['file']) {
      $file_path = $item['file path'] ? $item['file path'] : drupal_get_path('module', $item['module']);
      $item['include file'] = $file_path .'/'. $item['file'];
    }

    $title_arguments = $item['title arguments'] ? serialize($item['title arguments']) : '';
    db_query("INSERT INTO {menu_router}
      (path, load_functions, to_arg_functions, access_callback,
      access_arguments, page_callback, page_arguments, fit,
      number_parts, tab_parent, tab_root,
      title, title_callback, title_arguments,
      type, block_callback, description, position, weight, file)
      VALUES ('%s', '%s', '%s', '%s',
      '%s', '%s', '%s', %d,
      %d, '%s', '%s',
      '%s', '%s', '%s',
      %d, '%s', '%s', '%s', %d, '%s')",
      $path, $item['load_functions'], $item['to_arg_functions'], $item['access callback'],
      serialize($item['access arguments']), $item['page callback'], serialize($item['page arguments']), $item['_fit'],
      $item['_number_parts'], $item['tab_parent'], $item['tab_root'],
      $item['title'], $item['title callback'], $title_arguments,
      $item['type'], $item['block callback'], $item['description'], $item['position'], $item['weight'], $item['include file']);
  }
  // Sort the masks so they are in order of descending fit, and store them.
  $masks = array_keys($masks);
  rsort($masks);
  variable_set('menu_masks', $masks);

  return $menu;
}

/**
 * Returns TRUE if a path is external (e.g. http://example.com).
 */
function menu_path_is_external($path) {
  $colonpos = strpos($path, ':');
  return $colonpos !== FALSE && !preg_match('![/?#]!', substr($path, 0, $colonpos)) && filter_xss_bad_protocol($path, FALSE) == check_plain($path);
}

/**
 * Checks whether the site is off-line for maintenance.
 *
 * This function will log the current user out and redirect to front page
 * if the current user has no 'administer site configuration' permission.
 *
 * @return
 *   FALSE if the site is not off-line or its the login page or the user has
 *     'administer site configuration' permission.
 *   TRUE for anonymous users not on the login page if the site is off-line.
 */
function _menu_site_is_offline() {
  // Check if site is set to off-line mode.
  if (variable_get('site_offline', 0)) {
    // Check if the user has administration privileges.
    if (user_access('administer site configuration')) {
      // Ensure that the off-line message is displayed only once [allowing for
      // page redirects], and specifically suppress its display on the site
      // maintenance page.
      if (drupal_get_normal_path($_GET['q']) != 'admin/settings/site-maintenance') {
        drupal_set_message(l(t('Operating in off-line mode.'), 'admin/settings/site-maintenance'), 'status', FALSE);
      }
    }
    else {
      // Anonymous users get a FALSE at the login prompt, TRUE otherwise.
      if (user_is_anonymous()) {
        return $_GET['q'] != 'user' && $_GET['q'] != 'user/login';
      }
      // Logged in users are unprivileged here, so they are logged out.
      require_once drupal_get_path('module', 'user') .'/user.pages.inc';
      user_logout();
    }
  }
  return FALSE;
}

/**
 * Validates the path of a menu link being created or edited.
 *
 * @return
 *   TRUE if it is a valid path AND the current user has access permission,
 *   FALSE otherwise.
 */
function menu_valid_path($form_item) {
  global $menu_admin;
  $item = array();
  $path = $form_item['link_path'];
  // We indicate that a menu administrator is running the menu access check.
  $menu_admin = TRUE;
  if ($path == '<front>' || menu_path_is_external($path)) {
    $item = array('access' => TRUE);
  }
  elseif (preg_match('/\/\%/', $path)) {
    // Path is dynamic (ie 'user/%'), so check directly against menu_router table.
    if ($item = db_fetch_array(db_query("SELECT * FROM {menu_router} where path = '%s' ", $path))) {
      $item['link_path']  = $form_item['link_path'];
      $item['link_title'] = $form_item['link_title'];
      $item['external']   = FALSE;
      $item['options'] = '';
      _menu_link_translate($item);
    }
  }
  else {
    $item = menu_get_item($path);
  }
  $menu_admin = FALSE;
  return $item && $item['access'];
}

/**
 * @} End of "defgroup menu".
 */
