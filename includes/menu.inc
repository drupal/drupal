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
 * menu system is fundamental to the creation of complex modules. As a note,
 * this is related to, but separate from menu.module, which allows menus
 * (which in this context are hierarchical lists of links) to be customized from
 * the Drupal administrative interface.
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

/**
 * Internal menu flag -- menu item is the root of the menu tree.
 */
define('MENU_IS_ROOT', 0x0001);

/**
 * Internal menu flag -- menu item is visible in the menu tree.
 */
define('MENU_VISIBLE_IN_TREE', 0x0002);

/**
 * Internal menu flag -- menu item is visible in the breadcrumb.
 */
define('MENU_VISIBLE_IN_BREADCRUMB', 0x0004);

/**
 * Internal menu flag -- menu item links back to its parent.
 */
define('MENU_LINKS_TO_PARENT', 0x0008);

/**
 * Internal menu flag -- menu item can be modified by administrator.
 */
define('MENU_MODIFIED_BY_ADMIN', 0x0020);

/**
 * Internal menu flag -- menu item was created by administrator.
 */
define('MENU_CREATED_BY_ADMIN', 0x0040);

/**
 * Internal menu flag -- menu item is a local task.
 */
define('MENU_IS_LOCAL_TASK', 0x0080);

/**
 * Internal menu flag -- menu item is a local action.
 */
define('MENU_IS_LOCAL_ACTION', 0x0100);

/**
 * @} End of "Menu flags".
 */

/**
 * @defgroup menu_item_types Menu item types
 * @{
 * Definitions for various menu item types.
 *
 * Menu item definitions provide one of these constants, which are shortcuts for
 * combinations of @link menu_flags Menu flags @endlink.
 */

/**
 * Menu type -- A "normal" menu item that's shown in menu and breadcrumbs.
 *
 * Normal menu items show up in the menu tree and can be moved/hidden by
 * the administrator. Use this for most menu items. It is the default value if
 * no menu item type is specified.
 */
define('MENU_NORMAL_ITEM', MENU_VISIBLE_IN_TREE | MENU_VISIBLE_IN_BREADCRUMB);

/**
 * Menu type -- A hidden, internal callback, typically used for API calls.
 *
 * Callbacks simply register a path so that the correct function is fired
 * when the URL is accessed. They do not appear in menus or breadcrumbs.
 */
define('MENU_CALLBACK', 0x0000);

/**
 * Menu type -- A normal menu item, hidden until enabled by an administrator.
 *
 * Modules may "suggest" menu items that the administrator may enable. They act
 * just as callbacks do until enabled, at which time they act like normal items.
 * Note for the value: 0x0010 was a flag which is no longer used, but this way
 * the values of MENU_CALLBACK and MENU_SUGGESTED_ITEM are separate.
 */
define('MENU_SUGGESTED_ITEM', MENU_VISIBLE_IN_BREADCRUMB | 0x0010);

/**
 * Menu type -- A task specific to the parent item, usually rendered as a tab.
 *
 * Local tasks are menu items that describe actions to be performed on their
 * parent item. An example is the path "node/52/edit", which performs the
 * "edit" task on "node/52".
 */
define('MENU_LOCAL_TASK', MENU_IS_LOCAL_TASK | MENU_VISIBLE_IN_BREADCRUMB);

/**
 * Menu type -- The "default" local task, which is initially active.
 *
 * Every set of local tasks should provide one "default" task, that links to the
 * same path as its parent when clicked.
 */
define('MENU_DEFAULT_LOCAL_TASK', MENU_IS_LOCAL_TASK | MENU_LINKS_TO_PARENT | MENU_VISIBLE_IN_BREADCRUMB);

/**
 * Menu type -- An action specific to the parent, usually rendered as a link.
 *
 * Local actions are menu items that describe actions on the parent item such
 * as adding a new user, taxonomy term, etc.
 */
define('MENU_LOCAL_ACTION', MENU_IS_LOCAL_TASK | MENU_IS_LOCAL_ACTION | MENU_VISIBLE_IN_BREADCRUMB);

/**
 * @} End of "Menu item types".
 */

/**
 * @defgroup menu_context_types Menu context types
 * @{
 * Flags for use in the "context" attribute of menu router items.
 */

/**
 * Internal menu flag: Invisible local task.
 *
 * This flag may be used for local tasks like "Delete", so custom modules and
 * themes can alter the default context and expose the task by altering menu.
 */
define('MENU_CONTEXT_NONE', 0x0000);

/**
 * Internal menu flag: Local task should be displayed in page context.
 */
define('MENU_CONTEXT_PAGE', 0x0001);

/**
 * Internal menu flag: Local task should be displayed inline.
 */
define('MENU_CONTEXT_INLINE', 0x0002);

/**
 * @} End of "Menu context types".
 */

/**
 * @defgroup menu_status_codes Menu status codes
 * @{
 * Status codes for menu callbacks.
 */

/**
 * Internal menu status code -- Menu item was found.
 */
define('MENU_FOUND', 1);

/**
 * Internal menu status code -- Menu item was not found.
 */
define('MENU_NOT_FOUND', 2);

/**
 * Internal menu status code -- Menu item access is denied.
 */
define('MENU_ACCESS_DENIED', 3);

/**
 * Internal menu status code -- Menu item inaccessible because site is offline.
 */
define('MENU_SITE_OFFLINE', 4);

/**
 * Internal menu status code -- Everything is working fine.
 */
define('MENU_SITE_ONLINE', 5);

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
define('MENU_MAX_PARTS', 9);


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
 * any argument matches that part. We limit ourselves to using binary
 * numbers that correspond the patterns of wildcards of router items that
 * actually exists. This list of 'masks' is built in menu_rebuild().
 *
 * @param $parts
 *   An array of path parts, for the above example
 *   array('node', '12345', 'edit').
 *
 * @return
 *   An array which contains the ancestors and placeholders. Placeholders
 *   simply contain as many '%s' as the ancestors.
 */
function menu_get_ancestors($parts) {
  $number_parts = count($parts);
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
      // Check the bit on the $j offset.
      if ($i & (1 << $j)) {
        // Bit one means the original value.
        $current .= $parts[$length - $j];
      }
      else {
        // Bit zero means means wildcard.
        $current .= '%';
      }
      // Unless we are at offset 0, add a slash.
      if ($j) {
        $current .= '/';
      }
    }
    $ancestors[] = $current;
  }
  return $ancestors;
}

/**
 * Unserializes menu data, using a map to replace path elements.
 *
 * The menu system stores various path-related information (such as the 'page
 * arguments' and 'access arguments' components of a menu item) in the database
 * using serialized arrays, where integer values in the arrays represent
 * arguments to be replaced by values from the path. This function first
 * unserializes such menu information arrays, and then does the path
 * replacement.
 *
 * The path replacement acts on each integer-valued element of the unserialized
 * menu data array ($data) using a map array ($map, which is typically an array
 * of path arguments) as a list of replacements. For instance, if there is an
 * element of $data whose value is the number 2, then it is replaced in $data
 * with $map[2]; non-integer values in $data are left alone.
 *
 * As an example, an unserialized $data array with elements ('node_load', 1)
 * represents instructions for calling the node_load() function. Specifically,
 * this instruction says to use the path component at index 1 as the input
 * parameter to node_load(). If the path is 'node/123', then $map will be the
 * array ('node', 123), and the returned array from this function will have
 * elements ('node_load', 123), since $map[1] is 123. This return value will
 * indicate specifically that node_load(123) is to be called to load the node
 * whose ID is 123 for this menu item.
 *
 * @param $data
 *   A serialized array of menu data, as read from the database.
 * @param $map
 *   A path argument array, used to replace integer values in $data; an integer
 *   value N in $data will be replaced by value $map[N]. Typically, the $map
 *   array is generated from a call to the arg() function.
 *
 * @return
 *   The unserialized $data array, with path arguments replaced.
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
 *
 * @return
 *   The router item, an associate array corresponding to one row in the
 *   menu_router table. The value of key map holds the loaded objects. The
 *   value of key access is TRUE if the current user can access this page.
 *   The values for key title, page_arguments, access_arguments, and
 *   theme_arguments will be filled in based on the database values and the
 *   objects loaded.
 */
function menu_get_item($path = NULL, $router_item = NULL) {
  $router_items = &drupal_static(__FUNCTION__);
  if (!isset($path)) {
    $path = $_GET['q'];
  }
  if (isset($router_item)) {
    $router_items[$path] = $router_item;
  }
  if (!isset($router_items[$path])) {
    $original_map = arg(NULL, $path);

    // Since there is no limit to the length of $path, use a hash to keep it
    // short yet unique.
    $cid = 'menu_item:' . hash('sha256', $path);
    if ($cached = cache_get($cid, 'cache_menu')) {
      $router_item = $cached->data;
    }
    else {
      $parts = array_slice($original_map, 0, MENU_MAX_PARTS);
      $ancestors = menu_get_ancestors($parts);
      $router_item = db_query_range('SELECT * FROM {menu_router} WHERE path IN (:ancestors) ORDER BY fit DESC', 0, 1, array(':ancestors' => $ancestors))->fetchAssoc();
      cache_set($cid, $router_item, 'cache_menu');
    }
    if ($router_item) {
      // Allow modules to alter the router item before it is translated and
      // checked for access.
      drupal_alter('menu_get_item', $router_item, $path, $original_map);

      $map = _menu_translate($router_item, $original_map);
      $router_item['original_map'] = $original_map;
      if ($map === FALSE) {
        $router_items[$path] = FALSE;
        return FALSE;
      }
      if ($router_item['access']) {
        $router_item['map'] = $map;
        $router_item['page_arguments'] = array_merge(menu_unserialize($router_item['page_arguments'], $map), array_slice($map, $router_item['number_parts']));
        $router_item['theme_arguments'] = array_merge(menu_unserialize($router_item['theme_arguments'], $map), array_slice($map, $router_item['number_parts']));
      }
    }
    $router_items[$path] = $router_item;
  }
  return $router_items[$path];
}

/**
 * Execute the page callback associated with the current path.
 *
 * @param $path
 *   The drupal path whose handler is to be be executed. If set to NULL, then
 *   the current path is used.
 * @param $deliver
 *   (optional) A boolean to indicate whether the content should be sent to the
 *   browser using the appropriate delivery callback (TRUE) or whether to return
 *   the result to the caller (FALSE).
 */
function menu_execute_active_handler($path = NULL, $deliver = TRUE) {
  // Check if site is offline.
  $page_callback_result = _menu_site_is_offline() ? MENU_SITE_OFFLINE : MENU_SITE_ONLINE;

  // Allow other modules to change the site status but not the path because that
  // would not change the global variable. hook_url_inbound_alter() can be used
  // to change the path. Code later will not use the $read_only_path variable.
  $read_only_path = !empty($path) ? $path : $_GET['q'];
  drupal_alter('menu_site_status', $page_callback_result, $read_only_path);

  // Only continue if the site status is not set.
  if ($page_callback_result == MENU_SITE_ONLINE) {
    // Rebuild if we know it's needed, or if the menu masks are missing which
    // occurs rarely, likely due to a race condition of multiple rebuilds.
    if (variable_get('menu_rebuild_needed', FALSE) || !variable_get('menu_masks', array())) {
      menu_rebuild();
    }
    if ($router_item = menu_get_item($path)) {
      if ($router_item['access']) {
        if ($router_item['include_file']) {
          require_once DRUPAL_ROOT . '/' . $router_item['include_file'];
        }
        $page_callback_result = call_user_func_array($router_item['page_callback'], $router_item['page_arguments']);
      }
      else {
        $page_callback_result = MENU_ACCESS_DENIED;
      }
    }
    else {
      $page_callback_result = MENU_NOT_FOUND;
    }
  }

  // Deliver the result of the page callback to the browser, or if requested,
  // return it raw, so calling code can do more processing.
  if ($deliver) {
    $default_delivery_callback = (isset($router_item) && $router_item) ? $router_item['delivery_callback'] : NULL;
    drupal_deliver_page($page_callback_result, $default_delivery_callback);
  }
  else {
    return $page_callback_result;
  }
}

/**
 * Loads objects into the map as defined in the $item['load_functions'].
 *
 * @param $item
 *   A menu router or menu link item
 * @param $map
 *   An array of path arguments (ex: array('node', '5'))
 *
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
    if (!is_array($load_functions)) {
      $load_functions = unserialize($load_functions);
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
              // occurrences of the same placeholder can be identified.
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
 *
 * @return
 *   $item['access'] becomes TRUE if the item is accessible, FALSE otherwise.
 */
function _menu_check_access(&$item, $map) {
  // Determine access callback, which will decide whether or not the current
  // user has access to this path.
  $callback = empty($item['access_callback']) ? 0 : trim($item['access_callback']);
  // Check for a TRUE or FALSE value.
  if (is_numeric($callback)) {
    $item['access'] = (bool) $callback;
  }
  else {
    $arguments = menu_unserialize($item['access_arguments'], $map);
    // As call_user_func_array is quite slow and user_access is a very common
    // callback, it is worth making a special case for it.
    if ($callback == 'user_access') {
      $item['access'] = (count($arguments) == 1) ? user_access($arguments[0]) : user_access($arguments[0], $arguments[1]);
    }
    elseif (function_exists($callback)) {
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
 *
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
  // All 'class' attributes are assumed to be an array during rendering, but
  // links stored in the database may use an old string value.
  // @todo In order to remove this code we need to implement a database update
  //   including unserializing all existing link options and running this code
  //   on them, as well as adding validation to menu_link_save().
  if (isset($item['options']['attributes']['class']) && is_string($item['options']['attributes']['class'])) {
    $item['localized_options']['attributes']['class'] = explode(' ', $item['options']['attributes']['class']);
  }
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
    elseif ($callback && function_exists($callback)) {
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
 * to the language required to generate the current page.
 *
 * @param $router_item
 *   A menu router item
 * @param $map
 *   An array of path arguments (ex: array('node', '5'))
 * @param $to_arg
 *   Execute $item['to_arg_functions'] or not. Use only if you want to render a
 *   path from the menu table, for example tabs.
 *
 * @return
 *   Returns the map with objects loaded as defined in the
 *   $item['load_functions']. $item['access'] becomes TRUE if the item is
 *   accessible, FALSE otherwise. $item['href'] is set according to the map.
 *   If an error occurs during calling the load_functions (like trying to load
 *   a non existing node) then this function return FALSE.
 */
function _menu_translate(&$router_item, $map, $to_arg = FALSE) {
  if ($to_arg && !empty($router_item['to_arg_functions'])) {
    // Fill in missing path elements, such as the current uid.
    _menu_link_map_translate($map, $router_item['to_arg_functions']);
  }
  // The $path_map saves the pieces of the path as strings, while elements in
  // $map may be replaced with loaded objects.
  $path_map = $map;
  if (!empty($router_item['load_functions']) && !_menu_load_objects($router_item, $map)) {
    // An error occurred loading an object.
    $router_item['access'] = FALSE;
    return FALSE;
  }

  // Generate the link path for the page request or local tasks.
  $link_map = explode('/', $router_item['path']);
  if (isset($router_item['tab_root'])) {
    $tab_root_map = explode('/', $router_item['tab_root']);
  }
  if (isset($router_item['tab_parent'])) {
    $tab_parent_map = explode('/', $router_item['tab_parent']);
  }
  for ($i = 0; $i < $router_item['number_parts']; $i++) {
    if ($link_map[$i] == '%') {
      $link_map[$i] = $path_map[$i];
    }
    if (isset($tab_root_map[$i]) && $tab_root_map[$i] == '%') {
      $tab_root_map[$i] = $path_map[$i];
    }
    if (isset($tab_parent_map[$i]) && $tab_parent_map[$i] == '%') {
      $tab_parent_map[$i] = $path_map[$i];
    }
  }
  $router_item['href'] = implode('/', $link_map);
  $router_item['tab_root_href'] = implode('/', $tab_root_map);
  $router_item['tab_parent_href'] = implode('/', $tab_parent_map);
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
 * @param $map
 *   An array of path arguments (ex: array('node', '5'))
 * @param $to_arg_functions
 *   An array of helper function (ex: array(2 => 'menu_tail_to_arg'))
 */
function _menu_link_map_translate(&$map, $to_arg_functions) {
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

/**
 * Returns path as one string from the argument we are currently at.
 */
function menu_tail_to_arg($arg, $map, $index) {
  return implode('/', array_slice($map, $index));
}

/**
 * Loads path as one string from the argument we are currently at.
 *
 * To use this load function, you must specify the load arguments
 * in the router item as:
 * @code
 * $item['load arguments'] = array('%map', '%index');
 * @endcode
 *
 * @see search_menu().
 */
function menu_tail_load($arg, &$map, $index) {
  $arg = implode('/', array_slice($map, $index));
  $map = array_slice($map, 0, $index);
  return $arg;
}

/**
 * This function is similar to _menu_translate() but does link-specific
 * preparation such as always calling to_arg functions
 *
 * @param $item
 *   A menu link.
 * @param $translate
 *   (optional) Whether to try to translate a link containing dynamic path
 *   argument placeholders (%) based on the menu router item of the current
 *   path. Defaults to FALSE. Internally used for breadcrumbs.
 *
 * @return
 *   Returns the map of path arguments with objects loaded as defined in the
 *   $item['load_functions'].
 *   $item['access'] becomes TRUE if the item is accessible, FALSE otherwise.
 *   $item['href'] is generated from link_path, possibly by to_arg functions.
 *   $item['title'] is generated from link_title, and may be localized.
 *   $item['options'] is unserialized; it is also changed within the call here
 *   to $item['localized_options'] by _menu_item_localize().
 */
function _menu_link_translate(&$item, $translate = FALSE) {
  if (!is_array($item['options'])) {
    $item['options'] = unserialize($item['options']);
  }
  if ($item['external']) {
    $item['access'] = 1;
    $map = array();
    $item['href'] = $item['link_path'];
    $item['title'] = $item['link_title'];
    $item['localized_options'] = $item['options'];
  }
  else {
    // Complete the path of the menu link with elements from the current path,
    // if it contains dynamic placeholders (%).
    $map = explode('/', $item['link_path']);
    if (strpos($item['link_path'], '%') !== FALSE) {
      // Invoke registered to_arg callbacks.
      if (!empty($item['to_arg_functions'])) {
        _menu_link_map_translate($map, $item['to_arg_functions']);
      }
      // Or try to derive the path argument map from the current router item,
      // if this $item's path is within the router item's path. This means
      // that if we are on the current path 'foo/%/bar/%/baz', then
      // menu_get_item() will have translated the menu router item for the
      // current path, and we can take over the argument map for a link like
      // 'foo/%/bar'. This inheritance is only valid for breadcrumb links.
      // @see _menu_tree_check_access()
      // @see menu_get_active_breadcrumb()
      elseif ($translate && ($current_router_item = menu_get_item())) {
        // If $translate is TRUE, then this link is in the active trail.
        // Only translate paths within the current path.
        if (strpos($current_router_item['path'], $item['link_path']) === 0) {
          $count = count($map);
          $map = array_slice($current_router_item['original_map'], 0, $count);
          $item['original_map'] = $map;
          if (isset($current_router_item['map'])) {
            $item['map'] = array_slice($current_router_item['map'], 0, $count);
          }
          // Reset access to check it (for the first time).
          unset($item['access']);
        }
      }
    }
    $item['href'] = implode('/', $map);

    // Skip links containing untranslated arguments.
    if (strpos($item['href'], '%') !== FALSE) {
      $item['access'] = FALSE;
      return FALSE;
    }
    // menu_tree_check_access() may set this ahead of time for links to nodes.
    if (!isset($item['access'])) {
      if (!empty($item['load_functions']) && !_menu_load_objects($item, $map)) {
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
 * menu_get_object() provides access to objects loaded by the current router
 * item. For example, on the page node/%node, the router loads the %node object,
 * and calling menu_get_object() will return that. Normally, it is necessary to
 * specify the type of object referenced, however node is the default.
 * The following example tests to see whether the node being displayed is of the
 * "story" content type:
 * @code
 * $node = menu_get_object();
 * $story = $node->type == 'story';
 * @endcode
 *
 * @param $type
 *   Type of the object. These appear in hook_menu definitions as %type. Core
 *   provides aggregator_feed, aggregator_category, contact, filter_format,
 *   forum_term, menu, menu_link, node, taxonomy_vocabulary, user. See the
 *   relevant {$type}_load function for more on each. Defaults to node.
 * @param $position
 *   The position of the object in the path, where the first path segment is 0.
 *   For node/%node, the position of %node is 1, but for comment/reply/%node,
 *   it's 2. Defaults to 1.
 * @param $path
 *   See menu_get_item() for more on this. Defaults to the current path.
 */
function menu_get_object($type = 'node', $position = 1, $path = NULL) {
  $router_item = menu_get_item($path);
  if (isset($router_item['load_functions'][$position]) && !empty($router_item['map'][$position]) && $router_item['load_functions'][$position] == $type . '_load') {
    return $router_item['map'][$position];
  }
}

/**
 * Renders a menu tree based on the current path.
 *
 * The tree is expanded based on the current path and dynamic paths are also
 * changed according to the defined to_arg functions (for example the 'My
 * account' link is changed from user/% to a link with the current user's uid).
 *
 * @param $menu_name
 *   The name of the menu.
 *
 * @return
 *   A structured array representing the specified menu on the current page, to
 *   be rendered by drupal_render().
 */
function menu_tree($menu_name) {
  $menu_output = &drupal_static(__FUNCTION__, array());

  if (!isset($menu_output[$menu_name])) {
    $tree = menu_tree_page_data($menu_name);
    $menu_output[$menu_name] = menu_tree_output($tree);
  }
  return $menu_output[$menu_name];
}

/**
 * Returns a rendered menu tree.
 *
 * The menu item's LI element is given one of the following classes:
 * - expanded: The menu item is showing its submenu.
 * - collapsed: The menu item has a submenu which is not shown.
 * - leaf: The menu item has no submenu.
 *
 * @param $tree
 *   A data structure representing the tree as returned from menu_tree_data.
 *
 * @return
 *   A structured array to be rendered by drupal_render().
 */
function menu_tree_output($tree) {
  $build = array();
  $items = array();

  // Pull out just the menu links we are going to render so that we
  // get an accurate count for the first/last classes.
  foreach ($tree as $data) {
    if ($data['link']['access'] && !$data['link']['hidden']) {
      $items[] = $data;
    }
  }

  $router_item = menu_get_item();
  $num_items = count($items);
  foreach ($items as $i => $data) {
    $class = array();
    if ($i == 0) {
      $class[] = 'first';
    }
    if ($i == $num_items - 1) {
      $class[] = 'last';
    }
    // Set a class for the <li>-tag. Since $data['below'] may contain local
    // tasks, only set 'expanded' class if the link also has children within
    // the current menu.
    if ($data['link']['has_children'] && $data['below']) {
      $class[] = 'expanded';
    }
    elseif ($data['link']['has_children']) {
      $class[] = 'collapsed';
    }
    else {
      $class[] = 'leaf';
    }
    // Set a class if the link is in the active trail.
    if ($data['link']['in_active_trail']) {
      $class[] = 'active-trail';
      $data['link']['localized_options']['attributes']['class'][] = 'active-trail';
    }
    // Normally, l() compares the href of every link with $_GET['q'] and sets
    // the active class accordingly. But local tasks do not appear in menu
    // trees, so if the current path is a local task, and this link is its
    // tab root, then we have to set the class manually.
    if ($data['link']['href'] == $router_item['tab_root_href'] && $data['link']['href'] != $_GET['q']) {
      $data['link']['localized_options']['attributes']['class'][] = 'active';
    }

    // Allow menu-specific theme overrides.
    $element['#theme'] = 'menu_link__' . strtr($data['link']['menu_name'], '-', '_');
    $element['#attributes']['class'] = $class;
    $element['#title'] = $data['link']['title'];
    $element['#href'] = $data['link']['href'];
    $element['#localized_options'] = !empty($data['link']['localized_options']) ? $data['link']['localized_options'] : array();
    $element['#below'] = $data['below'] ? menu_tree_output($data['below']) : $data['below'];
    $element['#original_link'] = $data['link'];
    // Index using the link's unique mlid.
    $build[$data['link']['mlid']] = $element;
  }
  if ($build) {
    // Make sure drupal_render() does not re-order the links.
    $build['#sorted'] = TRUE;
    // Add the theme wrapper for outer markup.
    // Allow menu-specific theme overrides.
    $build['#theme_wrappers'][] = 'menu_tree__' . strtr($data['link']['menu_name'], '-', '_');
  }

  return $build;
}

/**
 * Get the data structure representing a named menu tree.
 *
 * Since this can be the full tree including hidden items, the data returned
 * may be used for generating an an admin interface or a select.
 *
 * @param $menu_name
 *   The named menu links to return
 * @param $link
 *   A fully loaded menu link, or NULL. If a link is supplied, only the
 *   path to root will be included in the returned tree - as if this link
 *   represented the current page in a visible menu.
 * @param $max_depth
 *   Optional maximum depth of links to retrieve. Typically useful if only one
 *   or two levels of a sub tree are needed in conjunction with a non-NULL
 *   $link, in which case $max_depth should be greater than $link['depth'].
 *
 * @return
 *   An tree of menu links in an array, in the order they should be rendered.
 */
function menu_tree_all_data($menu_name, $link = NULL, $max_depth = NULL) {
  $tree = &drupal_static(__FUNCTION__, array());

  // Use $mlid as a flag for whether the data being loaded is for the whole tree.
  $mlid = isset($link['mlid']) ? $link['mlid'] : 0;
  // Generate a cache ID (cid) specific for this $menu_name, $link, $language, and depth.
  $cid = 'links:' . $menu_name . ':all:' . $mlid . ':' . $GLOBALS['language']->language . ':' . (int) $max_depth;

  if (!isset($tree[$cid])) {
    // If the static variable doesn't have the data, check {cache_menu}.
    $cache = cache_get($cid, 'cache_menu');
    if ($cache && isset($cache->data)) {
      // If the cache entry exists, it contains the parameters for
      // menu_build_tree().
      $tree_parameters = $cache->data;
    }
    // If the tree data was not in the cache, build $tree_parameters.
    if (!isset($tree_parameters)) {
      $tree_parameters = array(
        'min_depth' => 1,
        'max_depth' => $max_depth,
      );
      if ($mlid) {
        // The tree is for a single item, so we need to match the values in its
        // p columns and 0 (the top level) with the plid values of other links.
        $parents = array(0);
        for ($i = 1; $i < MENU_MAX_DEPTH; $i++) {
          if (!empty($link["p$i"])) {
            $parents[] = $link["p$i"];
          }
        }
        $tree_parameters['expanded'] = $parents;
        $tree_parameters['active_trail'] = $parents;
        $tree_parameters['active_trail'][] = $mlid;
      }

      // Cache the tree building parameters using the page-specific cid.
      cache_set($cid, $tree_parameters, 'cache_menu');
    }

    // Build the tree using the parameters; the resulting tree will be cached
    // by _menu_build_tree()).
    $tree[$cid] = menu_build_tree($menu_name, $tree_parameters);
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
 *   The named menu links to return.
 * @param $max_depth
 *   (optional) The maximum depth of links to retrieve.
 * @param $only_active_trail
 *   (optional) Whether to only return the links in the active trail (TRUE)
 *   instead of all links on every level of the menu link tree (FALSE). Defaults
 *   to FALSE. Internally used for breadcrumbs only.
 *
 * @return
 *   An array of menu links, in the order they should be rendered. The array
 *   is a list of associative arrays -- these have two keys, link and below.
 *   link is a menu item, ready for theming as a link. Below represents the
 *   submenu below the link if there is one, and it is a subtree that has the
 *   same structure described for the top-level array.
 */
function menu_tree_page_data($menu_name, $max_depth = NULL, $only_active_trail = FALSE) {
  $tree = &drupal_static(__FUNCTION__, array());

  // Load the menu item corresponding to the current page.
  if ($item = menu_get_item()) {
    if (isset($max_depth)) {
      $max_depth = min($max_depth, MENU_MAX_DEPTH);
    }
    // Generate a cache ID (cid) specific for this page.
    $cid = 'links:' . $menu_name . ':page:' . $item['href'] . ':' . $GLOBALS['language']->language . ':' . (int) $item['access'] . ':' . (int) $max_depth;
    // If we are asked for the active trail only, and $menu_name has not been
    // built and cached for this page yet, then this likely means that it
    // won't be built anymore, as this function is invoked from
    // template_process_page(). So in order to not build a giant menu tree
    // that needs to be checked for access on all levels, we simply check
    // whether we have the menu already in cache, or otherwise, build a minimum
    // tree containing the breadcrumb/active trail only.
    // @see menu_set_active_trail()
    if (!isset($tree[$cid]) && $only_active_trail) {
      $cid .= ':trail';
    }

    if (!isset($tree[$cid])) {
      // If the static variable doesn't have the data, check {cache_menu}.
      $cache = cache_get($cid, 'cache_menu');
      if ($cache && isset($cache->data)) {
        // If the cache entry exists, it contains the parameters for
        // menu_build_tree().
        $tree_parameters = $cache->data;
      }
      // If the tree data was not in the cache, build $tree_parameters.
      if (!isset($tree_parameters)) {
        $tree_parameters = array(
          'min_depth' => 1,
          'max_depth' => $max_depth,
        );
        // Parent mlids; used both as key and value to ensure uniqueness.
        // We always want all the top-level links with plid == 0.
        $active_trail = array(0 => 0);

        // If the item for the current page is accessible, build the tree
        // parameters accordingly.
        if ($item['access']) {
          // Find a menu link corresponding to the current path.
          if ($active_link = menu_link_get_preferred()) {
            // The active link may only be taken into account to build the
            // active trail, if it resides in the requested menu. Otherwise,
            // we'd needlessly re-run _menu_build_tree() queries for every menu
            // on every page.
            if ($active_link['menu_name'] == $menu_name) {
              // Use all the coordinates, except the last one because there
              // can be no child beyond the last column.
              for ($i = 1; $i < MENU_MAX_DEPTH; $i++) {
                if ($active_link['p' . $i]) {
                  $active_trail[$active_link['p' . $i]] = $active_link['p' . $i];
                }
              }
              // If we are asked to build links for the active trail only, skip
              // the entire 'expanded' handling.
              if ($only_active_trail) {
                $tree_parameters['only_active_trail'] = TRUE;
              }
            }
          }
          $parents = $active_trail;

          $expanded = variable_get('menu_expanded', array());
          // Check whether the current menu has any links set to be expanded.
          if (!$only_active_trail && in_array($menu_name, $expanded)) {
            // Collect all the links set to be expanded, and then add all of
            // their children to the list as well.
            do {
              $result = db_select('menu_links', NULL, array('fetch' => PDO::FETCH_ASSOC))
                ->fields('menu_links', array('mlid'))
                ->condition('menu_name', $menu_name)
                ->condition('expanded', 1)
                ->condition('has_children', 1)
                ->condition('plid', $parents, 'IN')
                ->condition('mlid', $parents, 'NOT IN')
                ->execute();
              $num_rows = FALSE;
              foreach ($result as $item) {
                $parents[$item['mlid']] = $item['mlid'];
                $num_rows = TRUE;
              }
            } while ($num_rows);
          }
          $tree_parameters['expanded'] = $parents;
          $tree_parameters['active_trail'] = $active_trail;
        }
        // If access is denied, we only show top-level links in menus.
        else {
          $tree_parameters['expanded'] = $active_trail;
          $tree_parameters['active_trail'] = $active_trail;
        }
        // Cache the tree building parameters using the page-specific cid.
        cache_set($cid, $tree_parameters, 'cache_menu');
      }

      // Build the tree using the parameters; the resulting tree will be cached
      // by _menu_build_tree().
      $tree[$cid] = menu_build_tree($menu_name, $tree_parameters);
    }
    return $tree[$cid];
  }

  return array();
}

/**
 * Build a menu tree, translate links, and check access.
 *
 * @param $menu_name
 *   The name of the menu.
 * @param $parameters
 *   (optional) An associative array of build parameters. Possible keys:
 *   - expanded: An array of parent link ids to return only menu links that are
 *     children of one of the plids in this list. If empty, the whole menu tree
 *     is built, unless 'only_active_trail' is TRUE.
 *   - active_trail: An array of mlids, representing the coordinates of the
 *     currently active menu link.
 *   - only_active_trail: Whether to only return links that are in the active
 *     trail. This option is ignored, if 'expanded' is non-empty. Internally
 *     used for breadcrumbs.
 *   - min_depth: The minimum depth of menu links in the resulting tree.
 *     Defaults to 1, which is the default to build a whole tree for a menu, i.e.
 *     excluding menu container itself.
 *   - max_depth: The maximum depth of menu links in the resulting tree.
 *
 * @return
 *   A fully built menu tree.
 */
function menu_build_tree($menu_name, array $parameters = array()) {
  // Build the menu tree.
  $data = _menu_build_tree($menu_name, $parameters);
  // Check access for the current user to each item in the tree.
  menu_tree_check_access($data['tree'], $data['node_links']);
  return $data['tree'];
}

/**
 * Build a menu tree.
 *
 * This function may be used build the data for a menu tree only, for example
 * to further massage the data manually before further processing happens.
 * menu_tree_check_access() needs to be invoked afterwards.
 *
 * @see menu_build_tree()
 */
function _menu_build_tree($menu_name, array $parameters = array()) {
  // Static cache of already built menu trees.
  $trees = &drupal_static(__FUNCTION__, array());

  // Build the cache id; sort parents to prevent duplicate storage and remove
  // default parameter values.
  if (isset($parameters['expanded'])) {
    sort($parameters['expanded']);
  }
  $tree_cid = 'links:' . $menu_name . ':tree-data:' . $GLOBALS['language']->language . ':' . hash('sha256', serialize($parameters));

  // If we do not have this tree in the static cache, check {cache_menu}.
  if (!isset($trees[$tree_cid])) {
    $cache = cache_get($tree_cid, 'cache_menu');
    if ($cache && isset($cache->data)) {
      $trees[$tree_cid] = $cache->data;
    }
  }

  if (!isset($trees[$tree_cid])) {
    // Select the links from the table, and recursively build the tree. We
    // LEFT JOIN since there is no match in {menu_router} for an external
    // link.
    $query = db_select('menu_links', 'ml', array('fetch' => PDO::FETCH_ASSOC));
    $query->addTag('translatable');
    $query->leftJoin('menu_router', 'm', 'm.path = ml.router_path');
    $query->fields('ml');
    $query->fields('m', array(
      'load_functions',
      'to_arg_functions',
      'access_callback',
      'access_arguments',
      'page_callback',
      'page_arguments',
      'delivery_callback',
      'tab_parent',
      'tab_root',
      'title',
      'title_callback',
      'title_arguments',
      'theme_callback',
      'theme_arguments',
      'type',
      'description',
    ));
    for ($i = 1; $i <= MENU_MAX_DEPTH; $i++) {
      $query->orderBy('p' . $i, 'ASC');
    }
    $query->condition('ml.menu_name', $menu_name);
    if (!empty($parameters['expanded'])) {
      $query->condition('ml.plid', $parameters['expanded'], 'IN');
    }
    elseif (!empty($parameters['only_active_trail'])) {
      $query->condition('ml.mlid', $parameters['active_trail'], 'IN');
    }
    $min_depth = (isset($parameters['min_depth']) ? $parameters['min_depth'] : 1);
    if ($min_depth != 1) {
      $query->condition('ml.depth', $min_depth, '>=');
    }
    if (isset($parameters['max_depth'])) {
      $query->condition('ml.depth', $parameters['max_depth'], '<=');
    }

    // Build an ordered array of links using the query result object.
    $links = array();
    foreach ($query->execute() as $item) {
      $links[] = $item;
    }
    $active_trail = (isset($parameters['active_trail']) ? $parameters['active_trail'] : array());
    $data['tree'] = menu_tree_data($links, $active_trail, $min_depth);
    $data['node_links'] = array();
    menu_tree_collect_node_links($data['tree'], $data['node_links']);

    // Cache the data, if it is not already in the cache.
    cache_set($tree_cid, $data, 'cache_menu');
    $trees[$tree_cid] = $data;
  }

  return $trees[$tree_cid];
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
    $nids = array_keys($node_links);
    $select = db_select('node', 'n');
    $select->addField('n', 'nid');
    $select->condition('n.status', 1);
    $select->condition('n.nid', $nids, 'IN');
    $select->addTag('node_access');
    $nids = $select->execute()->fetchCol();
    foreach ($nids as $nid) {
      foreach ($node_links[$nid] as $mlid => $link) {
        $node_links[$nid][$mlid]['access'] = TRUE;
      }
    }
  }
  _menu_tree_check_access($tree);
}

/**
 * Recursive helper function for menu_tree_check_access()
 */
function _menu_tree_check_access(&$tree) {
  $new_tree = array();
  foreach ($tree as $key => $v) {
    $item = &$tree[$key]['link'];
    _menu_link_translate($item);
    if ($item['access'] || ($item['in_active_trail'] && strpos($item['href'], '%') !== FALSE)) {
      if ($tree[$key]['below']) {
        _menu_tree_check_access($tree[$key]['below']);
      }
      // The weights are made a uniform 5 digits by adding 50000 as an offset.
      // After _menu_link_translate(), $item['title'] has the localized link title.
      // Adding the mlid to the end of the index insures that it is unique.
      $new_tree[(50000 + $item['weight']) . ' ' . $item['title'] . ' ' . $item['mlid']] = $tree[$key];
    }
  }
  // Sort siblings in the tree based on the weights and localized titles.
  ksort($new_tree);
  $tree = $new_tree;
}

/**
 * Build the data representing a menu tree.
 *
 * @param $links
 *   An array of links (associative arrays) ordered by p1..p9.
 * @param $parents
 *   An array of the plid values that represent the path from the current page
 *   to the root of the menu tree.
 * @param $depth
 *   The minimum depth of any link in the $links array.
 *
 * @return
 *   See menu_tree_page_data for a description of the data structure.
 */
function menu_tree_data(array $links, array $parents = array(), $depth = 1) {
  // Reverse the array so we can use the more efficient array_pop() function.
  $links = array_reverse($links);
  return _menu_tree_data($links, $parents, $depth);
}

/**
 * Recursive helper function to build the data representing a menu tree.
 *
 * The function is a bit complex because the rendering of a link depends on
 * the next menu link.
 */
function _menu_tree_data(&$links, $parents, $depth) {
  $tree = array();
  while ($item = array_pop($links)) {
    // We need to determine if we're on the path to root so we can later build
    // the correct active trail and breadcrumb.
    $item['in_active_trail'] = in_array($item['mlid'], $parents);
    // Add the current link to the tree.
    $tree[$item['mlid']] = array(
      'link' => $item,
      'below' => array(),
    );
    // Look ahead to the next link, but leave it on the array so it's available
    // to other recursive function calls if we return or build a sub-tree.
    $next = end($links);
    // Check whether the next link is the first in a new sub-tree.
    if ($next && $next['depth'] > $depth) {
      // Recursively call _menu_tree_data to build the sub-tree.
      $tree[$item['mlid']]['below'] = _menu_tree_data($links, $parents, $next['depth']);
      // Fetch next link after filling the sub-tree.
      $next = end($links);
    }
    // Determine if we should exit the loop and return.
    if (!$next || $next['depth'] < $depth) {
      break;
    }
  }
  return $tree;
}

/**
 * Preprocesses the rendered tree for theme_menu_tree().
 */
function template_preprocess_menu_tree(&$variables) {
  $variables['tree'] = $variables['tree']['#children'];
}

/**
 * Returns HTML for a wrapper for a menu sub-tree.
 *
 * @param $variables
 *   An associative array containing:
 *   - tree: An HTML string containing the tree's items.
 *
 * @see template_preprocess_menu_tree()
 * @ingroup themeable
 */
function theme_menu_tree($variables) {
  return '<ul class="menu">' . $variables['tree'] . '</ul>';
}

/**
 * Returns HTML for a menu link and submenu.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: Structured array data for a menu link.
 *
 * @ingroup themeable
 */
function theme_menu_link(array $variables) {
  $element = $variables['element'];
  $sub_menu = '';

  if ($element['#below']) {
    $sub_menu = drupal_render($element['#below']);
  }
  $output = l($element['#title'], $element['#href'], $element['#localized_options']);
  return '<li' . drupal_attributes($element['#attributes']) . '>' . $output . $sub_menu . "</li>\n";
}

/**
 * Returns HTML for a single local task link.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: A render element containing:
 *     - #link: A menu link array with 'title', 'href', and 'localized_options'
 *       keys.
 *     - #active: A boolean indicating whether the local task is active.
 *
 * @ingroup themeable
 */
function theme_menu_local_task($variables) {
  $link = $variables['element']['#link'];
  $link_text = $link['title'];

  if (!empty($variables['element']['#active'])) {
    // Add text to indicate active tab for non-visual users.
    $active = '<span class="element-invisible">' . t('(active tab)') . '</span>';

    // If the link does not contain HTML already, check_plain() it now.
    // After we set 'html'=TRUE the link will not be sanitized by l().
    if (empty($link['localized_options']['html'])) {
      $link['title'] = check_plain($link['title']);
    }
    $link['localized_options']['html'] = TRUE;
    $link_text = t('!local-task-title!active', array('!local-task-title' => $link['title'], '!active' => $active));
  }

  return '<li' . (!empty($variables['element']['#active']) ? ' class="active"' : '') . '>' . l($link_text, $link['href'], $link['localized_options']) . "</li>\n";
}

/**
 * Returns HTML for a single local action link.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: A render element containing:
 *     - #link: A menu link array with 'title', 'href', and 'localized_options'
 *       keys.
 *
 * @ingroup themeable
 */
function theme_menu_local_action($variables) {
  $link = $variables['element']['#link'];

  $output = '<li>';
  if (isset($link['href'])) {
    $output .= l($link['title'], $link['href'], isset($link['localized_options']) ? $link['localized_options'] : array());
  }
  elseif (!empty($link['localized_options']['html'])) {
    $output .= $link['title'];
  }
  else {
    $output .= check_plain($link['title']);
  }
  $output .= "</li>\n";

  return $output;
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

  foreach (module_implements('help') as $module) {
    $function = $module . '_help';
    // Lookup help for this path.
    if ($help = $function($router_path, $arg)) {
      $output .= $help . "\n";
    }
  }
  return $output;
}

/**
 * Gets the custom theme for the current page, if there is one.
 *
 * @param $initialize
 *   This parameter should only be used internally; it is set to TRUE in order
 *   to force the custom theme to be initialized for the current page request.
 *
 * @return
 *   The machine-readable name of the custom theme, if there is one.
 *
 * @see menu_set_custom_theme()
 */
function menu_get_custom_theme($initialize = FALSE) {
  $custom_theme = &drupal_static(__FUNCTION__);
  // Skip this if the site is offline or being installed or updated, since the
  // menu system may not be correctly initialized then.
  if ($initialize && !_menu_site_is_offline(TRUE) && (!defined('MAINTENANCE_MODE') || (MAINTENANCE_MODE != 'update' && MAINTENANCE_MODE != 'install'))) {
    // First allow modules to dynamically set a custom theme for the current
    // page. Since we can only have one, the last module to return a valid
    // theme takes precedence.
    $custom_themes = array_filter(module_invoke_all('custom_theme'), 'drupal_theme_access');
    if (!empty($custom_themes)) {
      $custom_theme = array_pop($custom_themes);
    }
    // If there is a theme callback function for the current page, execute it.
    // If this returns a valid theme, it will override any theme that was set
    // by a hook_custom_theme() implementation above.
    $router_item = menu_get_item();
    if (!empty($router_item['access']) && !empty($router_item['theme_callback']) && function_exists($router_item['theme_callback'])) {
      $theme_name = call_user_func_array($router_item['theme_callback'], $router_item['theme_arguments']);
      if (drupal_theme_access($theme_name)) {
        $custom_theme = $theme_name;
      }
    }
  }
  return $custom_theme;
}

/**
 * Sets a custom theme for the current page, if there is one.
 */
function menu_set_custom_theme() {
  menu_get_custom_theme(TRUE);
}

/**
 * Build a list of named menus.
 */
function menu_get_names() {
  $names = &drupal_static(__FUNCTION__);

  if (empty($names)) {
    $names = db_select('menu_links')
      ->distinct()
      ->fields('menu_links', array('menu_name'))
      ->orderBy('menu_name')
      ->execute()->fetchCol();
  }
  return $names;
}

/**
 * Return an array containing the names of system-defined (default) menus.
 */
function menu_list_system_menus() {
  return array(
    'navigation' => 'Navigation',
    'management' => 'Management',
    'user-menu' => 'User menu',
    'main-menu' => 'Main menu',
  );
}

/**
 * Return an array of links to be rendered as the Main menu.
 */
function menu_main_menu() {
  return menu_navigation_links(variable_get('menu_main_links_source', 'main-menu'));
}

/**
 * Return an array of links to be rendered as the Secondary links.
 */
function menu_secondary_menu() {

  // If the secondary menu source is set as the primary menu, we display the
  // second level of the primary menu.
  if (variable_get('menu_secondary_links_source', 'user-menu') == variable_get('menu_main_links_source', 'main-menu')) {
    return menu_navigation_links(variable_get('menu_main_links_source', 'main-menu'), 1);
  }
  else {
    return menu_navigation_links(variable_get('menu_secondary_links_source', 'user-menu'), 0);
  }
}

/**
 * Return an array of links for a navigation menu.
 *
 * @param $menu_name
 *   The name of the menu.
 * @param $level
 *   Optional, the depth of the menu to be returned.
 *
 * @return
 *   An array of links of the specified menu and level.
 */
function menu_navigation_links($menu_name, $level = 0) {
  // Don't even bother querying the menu table if no menu is specified.
  if (empty($menu_name)) {
    return array();
  }

  // Get the menu hierarchy for the current page.
  $tree = menu_tree_page_data($menu_name, $level + 1);

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
  $router_item = menu_get_item();
  $links = array();
  foreach ($tree as $item) {
    if (!$item['link']['hidden']) {
      $class = '';
      $l = $item['link']['localized_options'];
      $l['href'] = $item['link']['href'];
      $l['title'] = $item['link']['title'];
      if ($item['link']['in_active_trail']) {
        $class = ' active-trail';
        $l['attributes']['class'][] = 'active-trail';
      }
      // Normally, l() compares the href of every link with $_GET['q'] and sets
      // the active class accordingly. But local tasks do not appear in menu
      // trees, so if the current path is a local task, and this link is its
      // tab root, then we have to set the class manually.
      if ($item['link']['href'] == $router_item['tab_root_href'] && $item['link']['href'] != $_GET['q']) {
        $l['attributes']['class'][] = 'active';
      }
      // Keyed with the unique mlid to generate classes in theme_links().
      $links['menu-' . $item['link']['mlid'] . $class] = $l;
    }
  }
  return $links;
}

/**
 * Collects the local tasks (tabs), action links, and the root path.
 *
 * @param $level
 *   The level of tasks you ask for. Primary tasks are 0, secondary are 1.
 *
 * @return
 *   An array containing
 *   - tabs: Local tasks for the requested level:
 *     - count: The number of local tasks.
 *     - output: The themed output of local tasks.
 *   - actions: Action links for the requested level:
 *     - count: The number of action links.
 *     - output: The themed output of action links.
 *   - root_path: The router path for the current page. If the current page is
 *     a default local task, then this corresponds to the parent tab.
 */
function menu_local_tasks($level = 0) {
  $data = &drupal_static(__FUNCTION__);
  $root_path = &drupal_static(__FUNCTION__ . ':root_path', '');
  $empty = array(
    'tabs' => array('count' => 0, 'output' => array()),
    'actions' => array('count' => 0, 'output' => array()),
    'root_path' => &$root_path,
  );

  if (!isset($data)) {
    $data = array();
    // Set defaults in case there are no actions or tabs.
    $actions = $empty['actions'];
    $tabs = array();

    $router_item = menu_get_item();

    // If this router item points to its parent, start from the parents to
    // compute tabs and actions.
    if ($router_item && ($router_item['type'] & MENU_LINKS_TO_PARENT)) {
      $router_item = menu_get_item($router_item['tab_parent_href']);
    }

    // If we failed to fetch a router item or the current user doesn't have
    // access to it, don't bother computing the tabs.
    if (!$router_item || !$router_item['access']) {
      return $empty;
    }

    // Get all tabs (also known as local tasks) and the root page.
    $result = db_select('menu_router', NULL, array('fetch' => PDO::FETCH_ASSOC))
      ->fields('menu_router')
      ->condition('tab_root', $router_item['tab_root'])
      ->condition('context', MENU_CONTEXT_INLINE, '<>')
      ->orderBy('weight')
      ->orderBy('title')
      ->execute();
    $map = $router_item['original_map'];
    $children = array();
    $tasks = array();
    $root_path = $router_item['path'];

    foreach ($result as $item) {
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
    $actions['count'] = 0;
    $actions['output'] = array();
    while (isset($children[$path])) {
      $tabs_current = array();
      $actions_current = array();
      $next_path = '';
      $tab_count = 0;
      $action_count = 0;
      foreach ($children[$path] as $item) {
        // Local tasks can be normal items too, so bitmask with
        // MENU_IS_LOCAL_TASK before checking.
        if (!($item['type'] & MENU_IS_LOCAL_TASK)) {
          // This item is not a tab, skip it.
          continue;
        }
        if ($item['access']) {
          $link = $item;
          // The default task is always active. As tabs can be normal items
          // too, so bitmask with MENU_LINKS_TO_PARENT before checking.
          if (($item['type'] & MENU_LINKS_TO_PARENT) == MENU_LINKS_TO_PARENT) {
            // Find the first parent which is not a default local task or action.
            for ($p = $item['tab_parent']; ($tasks[$p]['type'] & MENU_LINKS_TO_PARENT) == MENU_LINKS_TO_PARENT; $p = $tasks[$p]['tab_parent']);
            // Use the path of the parent instead.
            $link['href'] = $tasks[$p]['href'];
            // Mark the link as active, if the current path happens to be the
            // path of the default local task itself (i.e., instead of its
            // tab_parent_href or tab_root_href). Normally, links for default
            // local tasks link to their parent, but the path of default local
            // tasks can still be accessed directly, in which case this link
            // would not be marked as active, since l() only compares the href
            // with $_GET['q'].
            if ($link['href'] != $_GET['q']) {
              $link['localized_options']['attributes']['class'][] = 'active';
            }
            $tabs_current[] = array(
              '#theme' => 'menu_local_task',
              '#link' => $link,
              '#active' => TRUE,
            );
            $next_path = $item['path'];
            $tab_count++;
          }
          else {
            // Actions can be normal items too, so bitmask with
            // MENU_IS_LOCAL_ACTION before checking.
            if (($item['type'] & MENU_IS_LOCAL_ACTION) == MENU_IS_LOCAL_ACTION) {
              // The item is an action, display it as such.
              $actions_current[] = array(
                '#theme' => 'menu_local_action',
                '#link' => $link,
              );
              $action_count++;
            }
            else {
              // Otherwise, it's a normal tab.
              $tabs_current[] = array(
                '#theme' => 'menu_local_task',
                '#link' => $link,
              );
              $tab_count++;
            }
          }
        }
      }
      $path = $next_path;
      $tabs[$depth]['count'] = $tab_count;
      $tabs[$depth]['output'] = $tabs_current;
      $actions['count'] += $action_count;
      $actions['output'] = array_merge($actions['output'], $actions_current);
      $depth++;
    }
    $data['actions'] = $actions;
    // Find all tabs at the same level or above the current one.
    $parent = $router_item['tab_parent'];
    $path = $router_item['path'];
    $current = $router_item;
    $depth = 1000;
    while (isset($children[$parent])) {
      $tabs_current = array();
      $next_path = '';
      $next_parent = '';
      $count = 0;
      foreach ($children[$parent] as $item) {
        // Skip local actions.
        if ($item['type'] & MENU_IS_LOCAL_ACTION) {
          continue;
        }
        if ($item['access']) {
          $count++;
          $link = $item;
          // Local tasks can be normal items too, so bitmask with
          // MENU_LINKS_TO_PARENT before checking.
          if (($item['type'] & MENU_LINKS_TO_PARENT) == MENU_LINKS_TO_PARENT) {
            // Find the first parent which is not a default local task.
            for ($p = $item['tab_parent']; ($tasks[$p]['type'] & MENU_LINKS_TO_PARENT) == MENU_LINKS_TO_PARENT; $p = $tasks[$p]['tab_parent']);
            // Use the path of the parent instead.
            $link['href'] = $tasks[$p]['href'];
            if ($item['path'] == $router_item['path']) {
              $root_path = $tasks[$p]['path'];
            }
          }
          // We check for the active tab.
          if ($item['path'] == $path) {
            // Mark the link as active, if the current path is a (second-level)
            // local task of a default local task. Since this default local task
            // links to its parent, l() will not mark it as active, as it only
            // compares the link's href to $_GET['q'].
            if ($link['href'] != $_GET['q']) {
              $link['localized_options']['attributes']['class'][] = 'active';
            }
            $tabs_current[] = array(
              '#theme' => 'menu_local_task',
              '#link' => $link,
              '#active' => TRUE,
            );
            $next_path = $item['tab_parent'];
            if (isset($tasks[$next_path])) {
              $next_parent = $tasks[$next_path]['tab_parent'];
            }
          }
          else {
            $tabs_current[] = array(
              '#theme' => 'menu_local_task',
              '#link' => $link,
            );
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
    $data['tabs'] = $tabs;

    // Allow modules to alter local tasks or dynamically append further tasks.
    drupal_alter('menu_local_tasks', $data, $router_item, $root_path);
  }

  if (isset($data['tabs'][$level])) {
    return array(
      'tabs' => $data['tabs'][$level],
      'actions' => $data['actions'],
      'root_path' => $root_path,
    );
  }
  // @todo If there are no tabs, then there still can be actions; for example,
  //   when added via hook_menu_local_tasks_alter().
  elseif (!empty($data['actions']['output'])) {
    return array('actions' => $data['actions']) + $empty;
  }
  return $empty;
}

/**
 * Retrieve contextual links for a system object based on registered local tasks.
 *
 * This leverages the menu system to retrieve the first layer of registered
 * local tasks for a given system path. All local tasks of the tab type
 * MENU_CONTEXT_INLINE are taken into account.
 *
 * @see hook_menu()
 *
 * For example, when considering the following registered local tasks:
 * - node/%node/view (default local task) with no 'context' defined
 * - node/%node/edit with context: MENU_CONTEXT_PAGE | MENU_CONTEXT_INLINE
 * - node/%node/revisions with context: MENU_CONTEXT_PAGE
 * - node/%node/report-as-spam with context: MENU_CONTEXT_INLINE
 *
 * If the path "node/123" is passed to this function, then it will return the
 * links for 'edit' and 'report-as-spam'.
 *
 * @param $module
 *   The name of the implementing module. This is used to prefix the key for
 *   each contextual link, which is transformed into a CSS class during
 *   rendering by theme_links(). For example, if $module is 'block' and the
 *   retrieved local task path argument is 'edit', then the resulting CSS class
 *   will be 'block-edit'.
 * @param $parent_path
 *   The static menu router path of the object to retrieve local tasks for, for
 *   example 'node' or 'admin/structure/block/manage'.
 * @param $args
 *   A list of dynamic path arguments to append to $parent_path to form the
 *   fully-qualified menu router path, for example array(123) for a certain
 *   node or array('system', 'navigation') for a certain block.
 *
 * @return
 *   A list of menu router items that are local tasks for the passed-in path.
 *
 * @see contextual_links_preprocess()
 */
function menu_contextual_links($module, $parent_path, $args) {
  static $path_empty = array();

  $links = array();
  // Performance: In case a previous invocation for the same parent path did not
  // return any links, we immediately return here.
  if (isset($path_empty[$parent_path])) {
    return $links;
  }
  // Construct the item-specific parent path.
  $path = $parent_path . '/' . implode('/', $args);

  // Get the router item for the given parent link path.
  $router_item = menu_get_item($path);
  if (!$router_item || !$router_item['access']) {
    $path_empty[$parent_path] = TRUE;
    return $links;
  }
  $data = &drupal_static(__FUNCTION__, array());
  $root_path = $router_item['path'];

  // Performance: For a single, normalized path (such as 'node/%') we only query
  // available tasks once per request.
  if (!isset($data[$root_path])) {
    // Get all contextual links that are direct children of the router item and
    // not of the tab type 'view'.
    $data[$root_path] = db_select('menu_router', 'm')
      ->fields('m')
      ->condition('tab_parent', $router_item['tab_root'])
      ->condition('context', MENU_CONTEXT_NONE, '<>')
      ->condition('context', MENU_CONTEXT_PAGE, '<>')
      ->orderBy('weight')
      ->orderBy('title')
      ->execute()
      ->fetchAllAssoc('path', PDO::FETCH_ASSOC);
  }
  $parent_length = drupal_strlen($root_path) + 1;
  $map = $router_item['original_map'];
  foreach ($data[$root_path] as $item) {
    // Extract the actual "task" string from the path argument.
    $key = drupal_substr($item['path'], $parent_length);

    // Denormalize and translate the contextual link.
    _menu_translate($item, $map, TRUE);
    if (!$item['access']) {
      continue;
    }
    // All contextual links are keyed by the actual "task" path argument,
    // prefixed with the name of the implementing module.
    $links[$module . '-' . $key] = $item;
  }

  // Allow modules to alter contextual links.
  drupal_alter('menu_contextual_links', $links, $router_item, $root_path);

  // Performance: If the current user does not have access to any links for this
  // router path and no other module added further links, we assign FALSE here
  // to skip the entire process the next time the same router path is requested.
  if (empty($links)) {
    $path_empty[$parent_path] = TRUE;
  }

  return $links;
}

/**
 * Returns the rendered local tasks at the top level.
 */
function menu_primary_local_tasks() {
  $links = menu_local_tasks(0);
  // Do not display single tabs.
  return ($links['tabs']['count'] > 1 ? $links['tabs']['output'] : '');
}

/**
 * Returns the rendered local tasks at the second level.
 */
function menu_secondary_local_tasks() {
  $links = menu_local_tasks(1);
  // Do not display single tabs.
  return ($links['tabs']['count'] > 1 ? $links['tabs']['output'] : '');
}

/**
 * Returns the rendered local actions at the current level.
 */
function menu_local_actions() {
  $links = menu_local_tasks();
  return $links['actions']['output'];
}

/**
 * Returns the router path, or the path of the parent tab of a default local task.
 */
function menu_tab_root_path() {
  $links = menu_local_tasks();
  return $links['root_path'];
}

/**
 * Returns a renderable element for the primary and secondary tabs.
 */
function menu_local_tabs() {
  return array(
    '#theme' => 'menu_local_tasks',
    '#primary' => menu_primary_local_tasks(),
    '#secondary' => menu_secondary_local_tasks(),
  );
}

/**
 * Returns HTML for primary and secondary local tasks.
 *
 * @ingroup themeable
 */
function theme_menu_local_tasks(&$variables) {
  $output = '';

  if (!empty($variables['primary'])) {
    $variables['primary']['#prefix'] = '<h2 class="element-invisible">' . t('Primary tabs') . '</h2>';
    $variables['primary']['#prefix'] .= '<ul class="tabs primary">';
    $variables['primary']['#suffix'] = '</ul>';
    $output .= drupal_render($variables['primary']);
  }
  if (!empty($variables['secondary'])) {
    $variables['secondary']['#prefix'] = '<h2 class="element-invisible">' . t('Secondary tabs') . '</h2>';
    $variables['secondary']['#prefix'] .= '<ul class="tabs secondary">';
    $variables['secondary']['#suffix'] = '</ul>';
    $output .= drupal_render($variables['secondary']);
  }

  return $output;
}

/**
 * Set (or get) the active menu for the current page - determines the active trail.
 */
function menu_set_active_menu_names($menu_names = NULL) {
  $active = &drupal_static(__FUNCTION__);

  if (isset($menu_names) && is_array($menu_names)) {
    $active = $menu_names;
  }
  elseif (!isset($active)) {
    $active = variable_get('menu_default_active_menus', array_keys(menu_list_system_menus()));
  }
  return $active;
}

/**
 * Get the active menu for the current page - determines the active trail.
 */
function menu_get_active_menu_names() {
  return menu_set_active_menu_names();
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
 * Sets or gets the active trail (path to menu tree root) of the current page.
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
  $trail = &drupal_static(__FUNCTION__);

  if (isset($new_trail)) {
    $trail = $new_trail;
  }
  elseif (!isset($trail)) {
    $trail = array();
    $trail[] = array(
      'title' => t('Home'),
      'href' => '<front>',
      'link_path' => '',
      'localized_options' => array(),
      'type' => 0,
    );

    // Try to retrieve a menu link corresponding to the current path. If more
    // than one exists, the link from the most preferred menu is returned.
    $preferred_link = menu_link_get_preferred();
    $current_item = menu_get_item();

    // There is a link for the current path.
    if ($preferred_link) {
      // Pass TRUE for $only_active_trail to make menu_tree_page_data() build
      // a stripped down menu tree containing the active trail only, in case
      // the given menu has not been built in this request yet.
      $tree = menu_tree_page_data($preferred_link['menu_name'], NULL, TRUE);
      list($key, $curr) = each($tree);
    }
    // There is no link for the current path.
    else {
      $preferred_link = $current_item;
      $curr = FALSE;
    }

    while ($curr) {
      $link = $curr['link'];
      if ($link['in_active_trail']) {
        // Add the link to the trail, unless it links to its parent.
        if (!($link['type'] & MENU_LINKS_TO_PARENT)) {
          // The menu tree for the active trail may contain additional links
          // that have not been translated yet, since they contain dynamic
          // argument placeholders (%). Such links are not contained in regular
          // menu trees, and have only been loaded for the additional
          // translation that happens here, so as to be able to display them in
          // the breadcumb for the current page.
          // @see _menu_tree_check_access()
          // @see _menu_link_translate()
          if (strpos($link['href'], '%') !== FALSE) {
            _menu_link_translate($link, TRUE);
          }
          if ($link['access']) {
            $trail[] = $link;
          }
        }
        $tree = $curr['below'] ? $curr['below'] : array();
      }
      list($key, $curr) = each($tree);
    }
    // Make sure the current page is in the trail to build the page title, by
    // appending either the preferred link or the menu router item for the
    // current page. Exclude it if we are on the front page.
    $last = end($trail);
    if ($last['href'] != $preferred_link['href'] && !drupal_is_front_page()) {
      $trail[] = $preferred_link;
    }
  }
  return $trail;
}

/**
 * Lookup the preferred menu link for a given system path.
 *
 * @param $path
 *   The path, for example 'node/5'. The function will find the corresponding
 *   menu link ('node/5' if it exists, or fallback to 'node/%').
 *
 * @return
 *   A fully translated menu link, or NULL if not matching menu link was
 *   found. The most specific menu link ('node/5' preferred over 'node/%') in
 *   the most preferred menu (as defined by menu_get_active_menu_names()) is
 *   returned.
 */
function menu_link_get_preferred($path = NULL) {
  $preferred_links = &drupal_static(__FUNCTION__);

  if (!isset($path)) {
    $path = $_GET['q'];
  }

  if (!isset($preferred_links[$path])) {
    $preferred_links[$path] = FALSE;

    // Look for the correct menu link by building a list of candidate paths,
    // which are ordered by priority (translated hrefs are preferred over
    // untranslated paths). Afterwards, the most relevant path is picked from
    // the menus, ordered by menu preference.
    $item = menu_get_item($path);
    $path_candidates = array();
    // 1. The current item href.
    $path_candidates[$item['href']] = $item['href'];
    // 2. The tab root href of the current item (if any).
    if ($item['tab_parent'] && ($tab_root = menu_get_item($item['tab_root_href']))) {
      $path_candidates[$tab_root['href']] = $tab_root['href'];
    }
    // 3. The current item path (with wildcards).
    $path_candidates[$item['path']] = $item['path'];
    // 4. The tab root path of the current item (if any).
    if (!empty($tab_root)) {
      $path_candidates[$tab_root['path']] = $tab_root['path'];
    }

    // Retrieve a list of menu names, ordered by preference.
    $menu_names = menu_get_active_menu_names();

    $query = db_select('menu_links', 'ml', array('fetch' => PDO::FETCH_ASSOC));
    $query->leftJoin('menu_router', 'm', 'm.path = ml.router_path');
    $query->fields('ml');
    // Weight must be taken from {menu_links}, not {menu_router}.
    $query->addField('ml', 'weight', 'link_weight');
    $query->fields('m');
    $query->condition('ml.menu_name', $menu_names, 'IN');
    $query->condition('ml.link_path', $path_candidates, 'IN');

    // Sort candidates by link path and menu name.
    $candidates = array();
    foreach ($query->execute() as $candidate) {
      $candidate['weight'] = $candidate['link_weight'];
      $candidates[$candidate['link_path']][$candidate['menu_name']] = $candidate;
    }

    // Pick the most specific link, in the most preferred menu.
    foreach ($path_candidates as $link_path) {
      if (!isset($candidates[$link_path])) {
        continue;
      }
      foreach ($menu_names as $menu_name) {
        if (!isset($candidates[$link_path][$menu_name])) {
          continue;
        }
        $candidate_item = $candidates[$link_path][$menu_name];
        $map = explode('/', $path);
        _menu_translate($candidate_item, $map);
        if ($candidate_item['access']) {
          $preferred_links[$path] = $candidate_item;
        }
        break 2;
      }
    }
  }

  return $preferred_links[$path];
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
 *
 * @see menu_set_active_trail()
 */
function menu_get_active_breadcrumb() {
  $breadcrumb = array();

  // No breadcrumb for the front page.
  if (drupal_is_front_page()) {
    return $breadcrumb;
  }

  $item = menu_get_item();
  if (!empty($item['access'])) {
    $active_trail = menu_get_active_trail();

    // Allow modules to alter the breadcrumb, if possible, as that is much
    // faster than rebuilding an entirely new active trail.
    drupal_alter('menu_breadcrumb', $active_trail, $item);

    // Don't show a link to the current page in the breadcrumb trail.
    $end = end($active_trail);
    if ($item['href'] == $end['href']) {
      array_pop($active_trail);
    }

    // Remove the tab root (parent) if the current path links to its parent.
    // Normally, the tab root link is included in the breadcrumb, as soon as we
    // are on a local task or any other child link. However, if we are on a
    // default local task (e.g., node/%/view), then we do not want the tab root
    // link (e.g., node/%) to appear, as it would be identical to the current
    // page. Since this behavior also needs to work recursively (i.e., on
    // default local tasks of default local tasks), and since the last non-task
    // link in the trail is used as page title (see menu_get_active_title()),
    // this condition cannot be cleanly integrated into menu_get_active_trail().
    // menu_get_active_trail() already skips all links that link to their parent
    // (commonly MENU_DEFAULT_LOCAL_TASK). In order to also hide the parent link
    // itself, we always remove the last link in the trail, if the current
    // router item links to its parent.
    if (($item['type'] & MENU_LINKS_TO_PARENT) == MENU_LINKS_TO_PARENT) {
      array_pop($active_trail);
    }

    foreach ($active_trail as $parent) {
      $breadcrumb[] = l($parent['title'], $parent['href'], $parent['localized_options']);
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
    if (!(bool) ($item['type'] & MENU_IS_LOCAL_TASK)) {
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
 *
 * @return
 *   A menu link, with $item['access'] filled and link translated for
 *   rendering.
 */
function menu_link_load($mlid) {
  if (is_numeric($mlid)) {
    $query = db_select('menu_links', 'ml');
    $query->leftJoin('menu_router', 'm', 'm.path = ml.router_path');
    $query->fields('ml');
    // Weight should be taken from {menu_links}, not {menu_router}.
    $query->addField('ml', 'weight', 'link_weight');
    $query->fields('m');
    $query->condition('ml.mlid', $mlid);
    if ($item = $query->execute()->fetchAssoc()) {
      $item['weight'] = $item['link_weight'];
      _menu_link_translate($item);
      return $item;
    }
  }
  return FALSE;
}

/**
 * Clears the cached cached data for a single named menu.
 */
function menu_cache_clear($menu_name = 'navigation') {
  $cache_cleared = &drupal_static(__FUNCTION__, array());

  if (empty($cache_cleared[$menu_name])) {
    cache_clear_all('links:' . $menu_name . ':', 'cache_menu', TRUE);
    $cache_cleared[$menu_name] = 1;
  }
  elseif ($cache_cleared[$menu_name] == 1) {
    drupal_register_shutdown_function('cache_clear_all', 'links:' . $menu_name . ':', 'cache_menu', TRUE);
    $cache_cleared[$menu_name] = 2;
  }

  // Also clear the menu system static caches.
  menu_reset_static_cache();
}

/**
 * Clears all cached menu data. This should be called any time broad changes
 * might have been made to the router items or menu links.
 */
function menu_cache_clear_all() {
  cache_clear_all('*', 'cache_menu', TRUE);
  menu_reset_static_cache();
}

/**
 * Resets the menu system static cache.
 */
function menu_reset_static_cache() {
  drupal_static_reset('_menu_build_tree');
  drupal_static_reset('menu_tree');
  drupal_static_reset('menu_tree_all_data');
  drupal_static_reset('menu_tree_page_data');
  drupal_static_reset('menu_load_all');
  drupal_static_reset('menu_link_get_preferred');
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
 *
 * @return
 *   TRUE if the menu was rebuilt, FALSE if another thread was rebuilding
 *   in parallel and the current thread just waited for completion.
 */
function menu_rebuild() {
  if (!lock_acquire('menu_rebuild')) {
    // Wait for another request that is already doing this work.
    // We choose to block here since otherwise the router item may not
    // be available in menu_execute_active_handler() resulting in a 404.
    lock_wait('menu_rebuild');
    return FALSE;
  }

  $transaction = db_transaction();

  try {
    list($menu, $masks) = menu_router_build();
    _menu_router_save($menu, $masks);
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
  }
  catch (Exception $e) {
    $transaction->rollback();
    watchdog_exception('menu', $e);
  }

  lock_release('menu_rebuild');
  return TRUE;
}

/**
 * Collect and alter the menu definitions.
 */
function menu_router_build() {
  // We need to manually call each module so that we can know which module
  // a given item came from.
  $callbacks = array();
  foreach (module_implements('menu') as $module) {
    $router_items = call_user_func($module . '_menu');
    if (isset($router_items) && is_array($router_items)) {
      foreach (array_keys($router_items) as $path) {
        $router_items[$path]['module'] = $module;
      }
      $callbacks = array_merge($callbacks, $router_items);
    }
  }
  // Alter the menu as defined in modules, keys are like user/%user.
  drupal_alter('menu', $callbacks);
  list($menu, $masks) = _menu_router_build($callbacks);
  _menu_router_cache($menu);

  return array($menu, $masks);
}

/**
 * Helper function to store the menu router if we have it in memory.
 */
function _menu_router_cache($new_menu = NULL) {
  $menu = &drupal_static(__FUNCTION__);

  if (isset($new_menu)) {
    $menu = $new_menu;
  }
  return $menu;
}

/**
 * Get the menu router.
 */
function menu_get_router() {
  // Check first if we have it in memory already.
  $menu = _menu_router_cache();
  if (empty($menu)) {
    list($menu, $masks) = menu_router_build();
  }
  return $menu;
}

/**
 * Builds a link from a router item.
 */
function _menu_link_build($item) {
  // Suggested items are disabled by default.
  if ($item['type'] == MENU_SUGGESTED_ITEM) {
    $item['hidden'] = 1;
  }
  // Hide all items that are not visible in the tree.
  elseif (!($item['type'] & MENU_VISIBLE_IN_TREE)) {
    $item['hidden'] = -1;
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
      $menu_links[$path] = $item;
      $sort[$path] = $item['_number_parts'];
    }
  }
  if ($menu_links) {
    // Make sure no child comes before its parent.
    array_multisort($sort, SORT_NUMERIC, $menu_links);

    foreach ($menu_links as $item) {
      $existing_item = db_select('menu_links')
        ->fields('menu_links', array(
          'mlid',
          'menu_name',
          'plid',
          'customized',
          'has_children',
          'updated',
        ))
        ->condition('link_path', $item['path'])
        ->condition('module', 'system')
        ->execute()->fetchAssoc();
      if ($existing_item) {
        $item['mlid'] = $existing_item['mlid'];
        // A change in hook_menu may move the link to a different menu
        if (empty($item['menu_name']) || ($item['menu_name'] == $existing_item['menu_name'])) {
          $item['menu_name'] = $existing_item['menu_name'];
          $item['plid'] = $existing_item['plid'];
        }
        else {
          // It moved to a new menu. Let menu_link_save() try to find a new
          // parent based on the path.
          unset($item['plid']);
        }
        $item['has_children'] = $existing_item['has_children'];
        $item['updated'] = $existing_item['updated'];
      }
      if (!$existing_item || !$existing_item['customized']) {
        $item = _menu_link_build($item);
        menu_link_save($item);
      }
    }
  }
  $paths = array_keys($menu);
  // Updated and customized items whose router paths are gone need new ones.
  $result = db_select('menu_links', NULL, array('fetch' => PDO::FETCH_ASSOC))
    ->fields('menu_links', array(
      'link_path',
      'mlid',
      'router_path',
      'updated',
    ))
    ->condition(db_or()
      ->condition('updated', 1)
      ->condition(db_and()
        ->condition('router_path', $paths, 'NOT IN')
        ->condition('external', 0)
        ->condition('customized', 1)
      )
    )
    ->execute();
  foreach ($result as $item) {
    $router_path = _menu_find_router_path($item['link_path']);
    if (!empty($router_path) && ($router_path != $item['router_path'] || $item['updated'])) {
      // If the router path and the link path matches, it's surely a working
      // item, so we clear the updated flag.
      $updated = $item['updated'] && $router_path != $item['link_path'];
      db_update('menu_links')
        ->fields(array(
          'router_path' => $router_path,
          'updated' => (int) $updated,
        ))
        ->condition('mlid', $item['mlid'])
        ->execute();
    }
  }
  // Find any item whose router path does not exist any more.
  $result = db_select('menu_links')
    ->fields('menu_links')
    ->condition('router_path', $paths, 'NOT IN')
    ->condition('external', 0)
    ->condition('updated', 0)
    ->condition('customized', 0)
    ->orderBy('depth', 'DESC')
    ->execute();
  // Remove all such items. Starting from those with the greatest depth will
  // minimize the amount of re-parenting done by menu_link_delete().
  foreach ($result as $item) {
    _menu_delete_item($item, TRUE);
  }
}

/**
 * Clone an array of menu links.
 *
 * @param $links
 *   An array of menu links to clone.
 * @param $menu_name
 *   (optional) The name of a menu that the links will be cloned for. If not
 *   set, the cloned links will be in the same menu as the original set of
 *   links that were passed in.
 *
 * @return
 *   An array of menu links with the same properties as the passed-in array,
 *   but with the link identifiers removed so that a new link will be created
 *   when any of them is passed in to menu_link_save().
 *
 * @see menu_link_save()
 */
function menu_links_clone($links, $menu_name = NULL) {
  foreach ($links as &$link) {
    unset($link['mlid']);
    unset($link['plid']);
    if (isset($menu_name)) {
      $link['menu_name'] = $menu_name;
    }
  }
  return $links;
}

/**
 * Returns an array containing all links for a menu.
 *
 * @param $menu_name
 *   The name of the menu whose links should be returned.
 *
 * @return
 *   An array of menu links.
 */
function menu_load_links($menu_name) {
  $links = db_select('menu_links', 'ml', array('fetch' => PDO::FETCH_ASSOC))
    ->fields('ml')
    ->condition('ml.menu_name', $menu_name)
    // Order by weight so as to be helpful for menus that are only one level
    // deep.
    ->orderBy('weight')
    ->execute()
    ->fetchAll();

  foreach ($links as &$link) {
    $link['options'] = unserialize($link['options']);
  }
  return $links;
}

/**
 * Deletes all links for a menu.
 *
 * @param $menu_name
 *   The name of the menu whose links will be deleted.
 */
function menu_delete_links($menu_name) {
  $links = menu_load_links($menu_name);
  foreach ($links as $link) {
    // To speed up the deletion process, we reset some link properties that
    // would trigger re-parenting logic in _menu_delete_item() and
    // _menu_update_parental_status().
    $link['has_children'] = FALSE;
    $link['plid'] = 0;
    _menu_delete_item($link);
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
    _menu_delete_item(db_query("SELECT * FROM {menu_links} WHERE mlid = :mlid", array(':mlid' => $mlid))->fetchAssoc());
  }
  else {
    $result = db_query("SELECT * FROM {menu_links} WHERE link_path = :link_path", array(':link_path' => $path));
    foreach ($result as $link) {
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
  $item = is_object($item) ? get_object_vars($item) : $item;
  if ($item && ($item['module'] != 'system' || $item['updated'] || $force)) {
    // Children get re-attached to the item's parent.
    if ($item['has_children']) {
      $result = db_query("SELECT mlid FROM {menu_links} WHERE plid = :plid", array(':plid' => $item['mlid']));
      foreach ($result as $m) {
        $child = menu_link_load($m->mlid);
        $child['plid'] = $item['plid'];
        menu_link_save($child);
      }
    }

    // Notify modules we are deleting the item.
    module_invoke_all('menu_link_delete', $item);

    db_delete('menu_links')->condition('mlid', $item['mlid'])->execute();

    // Update the has_children status of the parent.
    _menu_update_parental_status($item);
    menu_cache_clear($item['menu_name']);
    _menu_clear_page_cache();
  }
}

/**
 * Saves a menu link.
 *
 * After calling this function, rebuild the menu cache using
 * menu_cache_clear_all().
 *
 * @param $item
 *   An associative array representing a menu link item, with elements:
 *   - link_path: (required) The path of the menu item, which should be
 *     normalized first by calling drupal_get_normal_path() on it.
 *   - link_title: (required) Title to appear in menu for the link.
 *   - menu_name: (optional) The machine name of the menu for the link.
 *     Defaults to 'navigation'.
 *   - weight: (optional) Integer to determine position in menu. Default is 0.
 *   - expanded: (optional) Boolean that determines if the item is expanded.
 *   - options: (optional) An array of options, see l() for more.
 *   - mlid: (optional) Menu link identifier, the primary integer key for each
 *     menu link. Can be set to an existing value, or to 0 or NULL
 *     to insert a new link.
 *   - plid: (optional) The mlid of the parent.
 *   - router_path: (optional) The path of the relevant router item.
 *
 * @return
 *   The mlid of the saved menu link, or FALSE if the menu link could not be
 *   saved.
 */
function menu_link_save(&$item) {
  drupal_alter('menu_link', $item);

  // This is the easiest way to handle the unique internal path '<front>',
  // since a path marked as external does not need to match a router path.
  $item['external'] = (url_is_external($item['link_path'])  || $item['link_path'] == '<front>') ? 1 : 0;
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
    if ($existing_item = db_query("SELECT * FROM {menu_links} WHERE mlid = :mlid", array(':mlid' => $item['mlid']))->fetchAssoc()) {
      $existing_item['options'] = unserialize($existing_item['options']);
    }
  }

  // Try to find a parent link. If found, assign it and derive its menu.
  $parent = _menu_link_find_parent($item);
  if (!empty($parent['mlid'])) {
    $item['plid'] = $parent['mlid'];
    $item['menu_name'] = $parent['menu_name'];
  }
  // If no corresponding parent link was found, move the link to the top-level.
  else {
    $item['plid'] = 0;
  }
  $menu_name = $item['menu_name'];

  if (!$existing_item) {
    $item['mlid'] = db_insert('menu_links')
      ->fields(array(
        'menu_name' => $item['menu_name'],
        'plid' => $item['plid'],
        'link_path' => $item['link_path'],
        'hidden' => $item['hidden'],
        'external' => $item['external'],
        'has_children' => $item['has_children'],
        'expanded' => $item['expanded'],
        'weight' => $item['weight'],
        'module' => $item['module'],
        'link_title' => $item['link_title'],
        'options' => serialize($item['options']),
        'customized' => $item['customized'],
        'updated' => $item['updated'],
      ))
      ->execute();
  }

  // Directly fill parents for top-level links.
  if ($item['plid'] == 0) {
    $item['p1'] = $item['mlid'];
    for ($i = 2; $i <= MENU_MAX_DEPTH; $i++) {
      $item["p$i"] = 0;
    }
    $item['depth'] = 1;
  }
  // Otherwise, ensure that this link's depth is not beyond the maximum depth
  // and fill parents based on the parent link.
  else {
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
  // Find the router_path.
  if (empty($item['router_path'])  || !$existing_item || ($existing_item['link_path'] != $item['link_path'])) {
    if ($item['external']) {
      $item['router_path'] = '';
    }
    else {
      // Find the router path which will serve this path.
      $item['parts'] = explode('/', $item['link_path'], MENU_MAX_PARTS);
      $item['router_path'] = _menu_find_router_path($item['link_path']);
    }
  }
  // If every value in $existing_item is the same in the $item, there is no
  // reason to run the update queries or clear the caches. We use
  // array_intersect_assoc() with the $item as the first parameter because
  // $item may have additional keys left over from building a router entry.
  // The intersect removes the extra keys, allowing a meaningful comparison.
  if (!$existing_item || (array_intersect_assoc($item, $existing_item)) != $existing_item) {
    db_update('menu_links')
      ->fields(array(
        'menu_name' => $item['menu_name'],
        'plid' => $item['plid'],
        'link_path' => $item['link_path'],
        'router_path' => $item['router_path'],
        'hidden' => $item['hidden'],
        'external' => $item['external'],
        'has_children' => $item['has_children'],
        'expanded' => $item['expanded'],
        'weight' => $item['weight'],
        'depth' => $item['depth'],
        'p1' => $item['p1'],
        'p2' => $item['p2'],
        'p3' => $item['p3'],
        'p4' => $item['p4'],
        'p5' => $item['p5'],
        'p6' => $item['p6'],
        'p7' => $item['p7'],
        'p8' => $item['p8'],
        'p9' => $item['p9'],
        'module' => $item['module'],
        'link_title' => $item['link_title'],
        'options' => serialize($item['options']),
        'customized' => $item['customized'],
      ))
      ->condition('mlid', $item['mlid'])
      ->execute();
    // Check the has_children status of the parent.
    _menu_update_parental_status($item);
    menu_cache_clear($menu_name);
    if ($existing_item && $menu_name != $existing_item['menu_name']) {
      menu_cache_clear($existing_item['menu_name']);
    }
    // Notify modules we have acted on a menu item.
    $hook = 'menu_link_insert';
    if ($existing_item) {
      $hook = 'menu_link_update';
    }
    module_invoke_all($hook, $item);
    // Now clear the cache.
    _menu_clear_page_cache();
  }
  return $item['mlid'];
}

/**
 * Find a possible parent for a given menu link.
 *
 * Because the parent of a given link might not exist anymore in the database,
 * we apply a set of heuristics to determine a proper parent:
 *
 *  - use the passed parent link if specified and existing.
 *  - else, use the first existing link down the previous link hierarchy
 *  - else, for system menu links (derived from hook_menu()), reparent
 *    based on the path hierarchy.
 *
 * @param $menu_link
 *   A menu link.
 * @return
 *   A menu link structure of the possible parent or FALSE if no valid parent
 *   has been found.
 */
function _menu_link_find_parent($menu_link) {
  $parent = FALSE;

  // This item is explicitely top-level, skip the rest of the parenting.
  if (isset($menu_link['plid']) && empty($menu_link['plid'])) {
    return $parent;
  }

  // If we have a parent link ID, try to use that.
  $candidates = array();
  if (isset($menu_link['plid'])) {
    $candidates[] = $menu_link['plid'];
  }

  // Else, if we have a link hierarchy try to find a valid parent in there.
  if (!empty($menu_link['depth']) && $menu_link['depth'] > 1) {
    for ($depth = $menu_link['depth'] - 1; $depth >= 1; $depth--) {
      $candidates[] = $menu_link['p' . $depth];
    }
  }

  foreach ($candidates as $mlid) {
    $parent = db_query("SELECT * FROM {menu_links} WHERE mlid = :mlid", array(':mlid' => $mlid))->fetchAssoc();
    if ($parent) {
      return $parent;
    }
  }

  // If everything else failed, try to derive the parent from the path
  // hierarchy. This only makes sense for links derived from menu router
  // items (ie. from hook_menu()).
  if ($menu_link['module'] == 'system') {
    $query = db_select('menu_links');
    $query->condition('module', 'system');
    // We always respect the link's 'menu_name'; inheritance for router items is
    // ensured in _menu_router_build().
    $query->condition('menu_name', $menu_link['menu_name']);

    // Find the parent - it must be unique.
    $parent_path = $menu_link['link_path'];
    do {
      $parent = FALSE;
      $parent_path = substr($parent_path, 0, strrpos($parent_path, '/'));
      $new_query = clone $query;
      $new_query->condition('link_path', $parent_path);
      // Only valid if we get a unique result.
      if ($new_query->countQuery()->execute()->fetchField() == 1) {
        $parent = $new_query->fields('menu_links')->execute()->fetchAssoc();
      }
    } while ($parent === FALSE && $parent_path);
  }

  return $parent;
}

/**
 * Helper function to clear the page and block caches at most twice per page load.
 */
function _menu_clear_page_cache() {
  $cache_cleared = &drupal_static(__FUNCTION__, 0);

  // Clear the page and block caches, but at most twice, including at
  //  the end of the page load when there are multiple links saved or deleted.
  if ($cache_cleared == 0) {
    cache_clear_all();
    // Keep track of which menus have expanded items.
    _menu_set_expanded_menus();
    $cache_cleared = 1;
  }
  elseif ($cache_cleared == 1) {
    drupal_register_shutdown_function('cache_clear_all');
    // Keep track of which menus have expanded items.
    drupal_register_shutdown_function('_menu_set_expanded_menus');
    $cache_cleared = 2;
  }
}

/**
 * Helper function to update a list of menus with expanded items
 */
function _menu_set_expanded_menus() {
  $names = db_query("SELECT menu_name FROM {menu_links} WHERE expanded <> 0 GROUP BY menu_name")->fetchCol();
  variable_set('menu_expanded', $names);
}

/**
 * Find the router path which will serve this path.
 *
 * @param $link_path
 *  The path for we are looking up its router path.
 *
 * @return
 *  A path from $menu keys or empty if $link_path points to a nonexisting
 *  place.
 */
function _menu_find_router_path($link_path) {
  // $menu will only have data during a menu rebuild.
  $menu = _menu_router_cache();

  $router_path = $link_path;
  $parts = explode('/', $link_path, MENU_MAX_PARTS);
  $ancestors = menu_get_ancestors($parts);

  if (empty($menu)) {
    // Not during a menu rebuild, so look up in the database.
    $router_path = (string) db_select('menu_router')
      ->fields('menu_router', array('path'))
      ->condition('path', $ancestors, 'IN')
      ->orderBy('fit', 'DESC')
      ->range(0, 1)
      ->execute()->fetchField();
  }
  elseif (!isset($menu[$router_path])) {
    // Add an empty router path as a fallback.
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
 *
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
      $result = db_query("SELECT * FROM {menu_links} WHERE link_path = :link_path AND module = :module AND customized = 0", array(':link_path' => $link_path, ':module' => $module))->fetchAll(PDO::FETCH_ASSOC);
      foreach ($result as $link) {
        $link['link_title'] = $link_title;
        $link['options'] = unserialize($link['options']);
        menu_link_save($link);
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
 *
 * @return
 *   The relative depth, or zero.
 *
 */
function menu_link_children_relative_depth($item) {
  $query = db_select('menu_links');
  $query->addField('menu_links', 'depth');
  $query->condition('menu_name', $item['menu_name']);
  $query->orderBy('depth', 'DESC');
  $query->range(0, 1);

  $i = 1;
  $p = 'p1';
  while ($i <= MENU_MAX_DEPTH && $item[$p]) {
    $query->condition($p, $item[$p]);
    $p = 'p' . ++$i;
  }

  $max_depth = $query->execute()->fetchField();

  return ($max_depth > $item['depth']) ? $max_depth - $item['depth'] : 0;
}

/**
 * Update the children of a menu link that's being moved.
 *
 * The menu name, parents (p1 - p6), and depth are updated for all children of
 * the link, and the has_children status of the previous parent is updated.
 */
function _menu_link_move_children($item, $existing_item) {
  $query = db_update('menu_links');

  $query->fields(array('menu_name' => $item['menu_name']));

  $p = 'p1';
  $expressions = array();
  for ($i = 1; $i <= $item['depth']; $p = 'p' . ++$i) {
    $expressions[] = array($p, ":p_$i", array(":p_$i" => $item[$p]));
  }
  $j = $existing_item['depth'] + 1;
  while ($i <= MENU_MAX_DEPTH && $j <= MENU_MAX_DEPTH) {
    $expressions[] = array('p' . $i++, 'p' . $j++, array());
  }
  while ($i <= MENU_MAX_DEPTH) {
    $expressions[] = array('p' . $i++, 0, array());
  }

  $shift = $item['depth'] - $existing_item['depth'];
  if ($shift > 0) {
    // The order of expressions must be reversed so the new values don't
    // overwrite the old ones before they can be used because "Single-table
    // UPDATE assignments are generally evaluated from left to right"
    // see: http://dev.mysql.com/doc/refman/5.0/en/update.html
    $expressions = array_reverse($expressions);
  }
  foreach ($expressions as $expression) {
    $query->expression($expression[0], $expression[1], $expression[2]);
  }

  $query->expression('depth', 'depth + :depth', array(':depth' => $shift));
  $query->condition('menu_name', $existing_item['menu_name']);
  $p = 'p1';
  for ($i = 1; $i <= MENU_MAX_DEPTH && $existing_item[$p]; $p = 'p' . ++$i) {
    $query->condition($p, $existing_item[$p]);
  }

  $query->execute();

  // Check the has_children status of the parent, while excluding this item.
  _menu_update_parental_status($existing_item, TRUE);
}

/**
 * Check and update the has_children status for the parent of a link.
 */
function _menu_update_parental_status($item, $exclude = FALSE) {
  // If plid == 0, there is nothing to update.
  if ($item['plid']) {
    // Check if at least one visible child exists in the table.
    $query = db_select('menu_links');
    $query->addField('menu_links', 'mlid');
    $query->condition('menu_name', $item['menu_name']);
    $query->condition('hidden', 0);
    $query->condition('plid', $item['plid']);
    $query->range(0, 1);
    if ($exclude) {
      $query->condition('mlid', $item['mlid'], '<>');
    }
    $parent_has_children = ((bool) $query->execute()->fetchField()) ? 1 : 0;
    db_update('menu_links')
      ->fields(array('has_children' => $parent_has_children))
      ->condition('mlid', $item['plid'])
      ->execute();
  }
}

/**
 * Helper function that sets the p1..p9 values for a menu link being saved.
 */
function _menu_link_parents_set(&$item, $parent) {
  $i = 1;
  while ($i < $item['depth']) {
    $p = 'p' . $i++;
    $item[$p] = $parent[$p];
  }
  $p = 'p' . $i++;
  // The parent (p1 - p9) corresponding to the depth always equals the mlid.
  $item[$p] = $item['mlid'];
  while ($i <= MENU_MAX_DEPTH) {
    $p = 'p' . $i++;
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
  $masks = array();
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
      if (preg_match('/^%(|' . DRUPAL_PHP_FUNCTION_PATTERN . ')$/', $part, $matches)) {
        if (empty($matches[1])) {
          $match = TRUE;
          $load_functions[$k] = NULL;
        }
        else {
          if (function_exists($matches[1] . '_to_arg')) {
            $to_arg_functions[$k] = $matches[1] . '_to_arg';
            $load_functions[$k] = NULL;
            $match = TRUE;
          }
          if (function_exists($matches[1] . '_load')) {
            $function = $matches[1] . '_load';
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
    $item['_load_functions'] = $load_functions;
    $item['to_arg_functions'] = empty($to_arg_functions) ? '' : serialize($to_arg_functions);
    $item += array(
      'title' => '',
      'weight' => 0,
      'type' => MENU_NORMAL_ITEM,
      'module' => '',
      '_number_parts' => $number_parts,
      '_parts' => $parts,
      '_fit' => $fit,
    );
    $item += array(
      '_visible' => (bool) ($item['type'] & MENU_VISIBLE_IN_BREADCRUMB),
      '_tab' => (bool) ($item['type'] & MENU_IS_LOCAL_TASK),
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
  // Apply inheritance rules.
  foreach ($menu as $path => $v) {
    $item = &$menu[$path];
    if (!$item['_tab']) {
      // Non-tab items.
      $item['tab_parent'] = '';
      $item['tab_root'] = $path;
    }
    // If not specified, assign the default tab type for local tasks.
    elseif (!isset($item['context'])) {
      $item['context'] = MENU_CONTEXT_PAGE;
    }
    for ($i = $item['_number_parts'] - 1; $i; $i--) {
      $parent_path = implode('/', array_slice($item['_parts'], 0, $i));
      if (isset($menu[$parent_path])) {

        $parent = &$menu[$parent_path];

        // If we have no menu name, try to inherit it from parent items.
        if (!isset($item['menu_name'])) {
          // If the parent item of this item does not define a menu name (and no
          // previous iteration assigned one already), try to find the menu name
          // of the parent item in the currently stored menu links.
          if (!isset($parent['menu_name'])) {
            $menu_name = db_query("SELECT menu_name FROM {menu_links} WHERE router_path = :router_path AND module = 'system'", array(':router_path' => $parent_path))->fetchField();
            if ($menu_name) {
              $parent['menu_name'] = $menu_name;
            }
          }
          // If the parent item defines a menu name, inherit it.
          if (!empty($parent['menu_name'])) {
            $item['menu_name'] = $parent['menu_name'];
          }
        }
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
          if (!isset($item['file path']) && isset($parent['file path'])) {
            $item['file path'] = $parent['file path'];
          }
          if (!isset($item['file']) && isset($parent['file'])) {
            $item['file'] = $parent['file'];
            if (empty($item['file path']) && isset($item['module']) && isset($parent['module']) && $item['module'] != $parent['module']) {
              $item['file path'] = drupal_get_path('module', $parent['module']);
            }
          }
        }
        // Same for delivery callbacks.
        if (!isset($item['delivery callback']) && isset($parent['delivery callback'])) {
          $item['delivery callback'] = $parent['delivery callback'];
        }
        // Same for theme callbacks.
        if (!isset($item['theme callback']) && isset($parent['theme callback'])) {
          $item['theme callback'] = $parent['theme callback'];
          if (!isset($item['theme arguments']) && isset($parent['theme arguments'])) {
            $item['theme arguments'] = $parent['theme arguments'];
          }
        }
        // Same for load arguments: if a loader doesn't have any explict
        // arguments, try to find arguments in the parent.
        if (!isset($item['load arguments'])) {
          foreach ($item['_load_functions'] as $k => $function) {
            // This loader doesn't have any explict arguments...
            if (!is_array($function)) {
              // ... check the parent for a loader at the same position
              // using the same function name and defining arguments...
              if (isset($parent['_load_functions'][$k]) && is_array($parent['_load_functions'][$k]) && key($parent['_load_functions'][$k]) === $function) {
                // ... and inherit the arguments on the child.
                $item['_load_functions'][$k] = $parent['_load_functions'][$k];
              }
            }
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

    $item['load_functions'] = empty($item['_load_functions']) ? '' : serialize($item['_load_functions']);
    $item += array(
      'access arguments' => array(),
      'access callback' => '',
      'page arguments' => array(),
      'page callback' => '',
      'delivery callback' => '',
      'title arguments' => array(),
      'title callback' => 't',
      'theme arguments' => array(),
      'theme callback' => '',
      'description' => '',
      'position' => '',
      'context' => 0,
      'tab_parent' => '',
      'tab_root' => $path,
      'path' => $path,
      'file' => '',
      'file path' => '',
      'include file' => '',
    );

    // Calculate out the file to be included for each callback, if any.
    if ($item['file']) {
      $file_path = $item['file path'] ? $item['file path'] : drupal_get_path('module', $item['module']);
      $item['include file'] = $file_path . '/' . $item['file'];
    }
  }

  // Sort the masks so they are in order of descending fit.
  $masks = array_keys($masks);
  rsort($masks);

  return array($menu, $masks);
}

/**
 * Helper function to save data from menu_router_build() to the router table.
 */
function _menu_router_save($menu, $masks) {
  // Delete the existing router since we have some data to replace it.
  db_truncate('menu_router')->execute();

  // Prepare insert object.
  $insert = db_insert('menu_router')
    ->fields(array(
      'path',
      'load_functions',
      'to_arg_functions',
      'access_callback',
      'access_arguments',
      'page_callback',
      'page_arguments',
      'delivery_callback',
      'fit',
      'number_parts',
      'context',
      'tab_parent',
      'tab_root',
      'title',
      'title_callback',
      'title_arguments',
      'theme_callback',
      'theme_arguments',
      'type',
      'description',
      'position',
      'weight',
      'include_file',
    ));

  $num_records = 0;

  foreach ($menu as $path => $item) {
    // Fill in insert object values.
    $insert->values(array(
      'path' => $item['path'],
      'load_functions' => $item['load_functions'],
      'to_arg_functions' => $item['to_arg_functions'],
      'access_callback' => $item['access callback'],
      'access_arguments' => serialize($item['access arguments']),
      'page_callback' => $item['page callback'],
      'page_arguments' => serialize($item['page arguments']),
      'delivery_callback' => $item['delivery callback'],
      'fit' => $item['_fit'],
      'number_parts' => $item['_number_parts'],
      'context' => $item['context'],
      'tab_parent' => $item['tab_parent'],
      'tab_root' => $item['tab_root'],
      'title' => $item['title'],
      'title_callback' => $item['title callback'],
      'title_arguments' => ($item['title arguments'] ? serialize($item['title arguments']) : ''),
      'theme_callback' => $item['theme callback'],
      'theme_arguments' => serialize($item['theme arguments']),
      'type' => $item['type'],
      'description' => $item['description'],
      'position' => $item['position'],
      'weight' => $item['weight'],
      'include_file' => $item['include file'],
    ));

    // Execute in batches to avoid the memory overhead of all of those records
    // in the query object.
    if (++$num_records == 20) {
      $insert->execute();
      $num_records = 0;
    }
  }
  // Insert any remaining records.
  $insert->execute();
  // Store the masks.
  variable_set('menu_masks', $masks);

  return $menu;
}

/**
 * Checks whether the site is in maintenance mode.
 *
 * This function will log the current user out and redirect to front page
 * if the current user has no 'access site in maintenance mode' permission.
 *
 * @param $check_only
 *   If this is set to TRUE, the function will perform the access checks and
 *   return the site offline status, but not log the user out or display any
 *   messages.
 *
 * @return
 *   FALSE if the site is not in maintenance mode, the user login page is
 *   displayed, or the user has the 'access site in maintenance mode'
 *   permission. TRUE for anonymous users not being on the login page when the
 *   site is in maintenance mode.
 */
function _menu_site_is_offline($check_only = FALSE) {
  // Check if site is in maintenance mode.
  if (variable_get('maintenance_mode', 0)) {
    if (user_access('access site in maintenance mode')) {
      // Ensure that the maintenance mode message is displayed only once
      // (allowing for page redirects) and specifically suppress its display on
      // the maintenance mode settings page.
      if (!$check_only && $_GET['q'] != 'admin/config/development/maintenance') {
        if (user_access('administer site configuration')) {
          drupal_set_message(t('Operating in maintenance mode. <a href="@url">Go online.</a>', array('@url' => url('admin/config/development/maintenance'))), 'status', FALSE);
        }
        else {
          drupal_set_message(t('Operating in maintenance mode.'), 'status', FALSE);
        }
      }
    }
    else {
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * @} End of "defgroup menu".
 */
