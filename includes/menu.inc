<?php
// $Id: menu.inc,v 1.154 2007/02/14 16:43:38 dries Exp $

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
 * in the "callback arguments" attribute of its menu item. The
 * attribute must be an array. After these arguments, any remaining
 * components of the path are appended as further arguments. In this
 * way, the callback for a/b above could respond to a request for
 * a/b/i differently than a request for a/b/j.
 *
 * For an illustration of this process, see page_example.module.
 *
 * Access to the callback functions is also protected by the menu system.
 * The "access" attribute of each menu item is checked as the search for a
 * callback proceeds. If this attribute is TRUE, then access is granted; if
 * FALSE, then access is denied. The first found "access" attribute
 * determines the accessibility of the target. Menu items may omit this
 * attribute to use the value provided by an ancestor item.
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
 */

/**
 * @name Menu flags
 * @{
 * Flags for use in the "type" attribute of menu items.
 */

define('MENU_IS_ROOT', 0x0001);
define('MENU_VISIBLE_IN_TREE', 0x0002);
define('MENU_VISIBLE_IN_BREADCRUMB', 0x0004);
define('MENU_VISIBLE_IF_HAS_CHILDREN', 0x0008);
define('MENU_MODIFIABLE_BY_ADMIN', 0x0010);
define('MENU_MODIFIED_BY_ADMIN', 0x0020);
define('MENU_CREATED_BY_ADMIN', 0x0040);
define('MENU_IS_LOCAL_TASK', 0x0080);
define('MENU_EXPANDED', 0x0100);
define('MENU_LINKS_TO_PARENT', 0x0200);

/**
 * @} End of "Menu flags".
 */

/**
 * @name Menu item types
 * @{
 * Menu item definitions provide one of these constants, which are shortcuts for
 * combinations of the above flags.
 */

/**
 * Normal menu items show up in the menu tree and can be moved/hidden by
 * the administrator. Use this for most menu items. It is the default value if
 * no menu item type is specified.
 */
define('MENU_NORMAL_ITEM', MENU_VISIBLE_IN_TREE | MENU_VISIBLE_IN_BREADCRUMB | MENU_MODIFIABLE_BY_ADMIN);

/**
 * Item groupings are used for pages like "node/add" that simply list
 * subpages to visit. They are distinguished from other pages in that they will
 * disappear from the menu if no subpages exist.
 */
define('MENU_ITEM_GROUPING', MENU_VISIBLE_IF_HAS_CHILDREN | MENU_VISIBLE_IN_BREADCRUMB | MENU_MODIFIABLE_BY_ADMIN);

/**
 * Callbacks simply register a path so that the correct function is fired
 * when the URL is accessed. They are not shown in the menu.
 */
define('MENU_CALLBACK', MENU_VISIBLE_IN_BREADCRUMB);

/**
 * Dynamic menu items change frequently, and so should not be stored in the
 * database for administrative customization.
 */
define('MENU_DYNAMIC_ITEM', MENU_VISIBLE_IN_TREE | MENU_VISIBLE_IN_BREADCRUMB);

/**
 * Modules may "suggest" menu items that the administrator may enable. They act
 * just as callbacks do until enabled, at which time they act like normal items.
 */
define('MENU_SUGGESTED_ITEM', MENU_MODIFIABLE_BY_ADMIN | MENU_VISIBLE_IN_BREADCRUMB);

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
 * Custom items are those defined by the administrator. Reserved for internal
 * use; do not return from hook_menu() implementations.
 */
define('MENU_CUSTOM_ITEM', MENU_VISIBLE_IN_TREE | MENU_VISIBLE_IN_BREADCRUMB | MENU_CREATED_BY_ADMIN | MENU_MODIFIABLE_BY_ADMIN);

/**
 * Custom menus are those defined by the administrator. Reserved for internal
 * use; do not return from hook_menu() implementations.
 */
define('MENU_CUSTOM_MENU', MENU_IS_ROOT | MENU_VISIBLE_IN_TREE | MENU_CREATED_BY_ADMIN | MENU_MODIFIABLE_BY_ADMIN);

/**
 * @} End of "Menu item types".
 */

/**
 * @name Menu status codes
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
 * @Name Menu operations
 * @{
 * Menu helper possible operations.
 */

define('MENU_HANDLE_REQUEST', 0);
define('MENU_RENDER_LINK', 1);

/**
 * @} End of "Menu helper directions
 */

/**
 * Returns the ancestors (and relevant placeholders) for any given path.
 *
 * For example, the ancestors of node/12345/edit are:
 *
 * node/12345/edit
 * node/12345/%
 * node/%/edit
 * node/%/%
 * node/12345
 * node/%
 * node
 *
 * To generate these, we will use binary numbers. Each bit represents a
 * part of the path. If the bit is 1, then it represents the original
 * value while 0 means wildcard. If the path is node/12/edit/foo
 * then the 1011 bitstring represents node/%/edit/foo where % means that
 * any argument matches that part.
 *
 * @param $parts
 *   An array of path parts, for the above example
 *   array('node', '12345', 'edit').
 * @return
 *   An array which contains the ancestors and placeholders. Placeholders
 *   simply contain as many '%s' as the ancestors.
 */
function menu_get_ancestors($parts) {
  $n1 = count($parts);
  $placeholders = array();
  $ancestors = array();
  $end = (1 << $n1) - 1;
  $length = $n1 - 1;
  for ($i = $end; $i > 0; $i--) {
    $current = '';
    $count = 0;
    for ($j = $length; $j >= 0; $j--) {
      if ($i & (1 << $j)) {
        $count++;
        $current .= $parts[$length - $j];
      }
      else {
        $current .= '%';
      }
      if ($j) {
        $current .= '/';
      }
    }
    // If the number was like 10...0 then the next number will be 11...11,
    // one bit less wide.
    if ($count == 1) {
      $length--;
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
 * example, if unserialize($data) is array('node_load', 1) and $map is
 * array('node', '12345') then 'node_load' will not be changed
 * because it is not an integer, but 1 will as it is an integer. As
 * $map[1] is '12345', 1 will be replaced with '12345'. So the result
 * will be array('node_load', '12345').
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
 *   The path
 * @param $item
 *   The menu item. This is a menu entry, an associative array,
 *   with keys like title, access callback, access arguments etc.
 */
function menu_set_item($path, $item) {
  menu_get_item($path, $item);
}

function menu_get_item($path = NULL, $item = NULL) {
  static $items;
  if (!isset($path)) {
    $path = $_GET['q'];
  }
  if (isset($item)) {
    $items[$path] = $item;
  }
  if (!isset($items[$path])) {
    $map = arg(NULL, $path);
    $parts = array_slice($map, 0, 6);
    list($ancestors, $placeholders) = menu_get_ancestors($parts);
    if ($item = db_fetch_object(db_query_range('SELECT * FROM {menu} WHERE path IN ('. implode (',', $placeholders) .') ORDER BY fit DESC', $ancestors, 0, 1))) {
      list($item->access, $map) = _menu_translate($item, $map);
      if ($map === FALSE) {
        $items[$path] = FALSE;
        return FALSE;
      }
      $item->map = $map;
      $item->page_arguments = array_merge(menu_unserialize($item->page_arguments, $map), array_slice($parts, $item->number_parts));
    }
    $items[$path] = $item;
  }
  return $items[$path];
}

/**
 * Execute the handler associated with the active menu item.
 */
function menu_execute_active_handler() {
  if ($item = menu_get_item()) {
    return $item->access ? call_user_func_array($item->page_callback, $item->page_arguments) : MENU_ACCESS_DENIED;
  }
  return MENU_NOT_FOUND;
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
 * This operation is called MENU_RENDER_LINK.
 *
 * @param $item
 *   A menu item object
 * @param $map
 *   An array of path arguments (ex: array('node', '5'))
 * @param $operation
 *   The path translation operation to perform:
 *   - MENU_HANDLE_REQUEST: An incoming page reqest; map with appropriate callback.
 *   - MENU_RENDER_LINK: Render an internal path as a link.
 * @return
 *   Returns an array. The first value is the access, the second is the map
 *   with objects loaded where appropriate and the third is the path ready for
 *   printing.
 */
function _menu_translate($item, $map, $operation = MENU_HANDLE_REQUEST) {
  $path = '';

  // Check if there are dynamic arguments in the path that need to be calculated.
  if ($item->load_functions || ($operation == MENU_RENDER_LINK && $item->to_arg_functions)) {
    $load_functions = unserialize($item->load_functions);
    $to_arg_functions = unserialize($item->to_arg_functions);
    $path_map = ($operation == MENU_HANDLE_REQUEST) ? $map : explode('/', $item->path);
    foreach ($load_functions as $index => $load_function) {
      // Translate place-holders into real values.
      if ($operation == MENU_RENDER_LINK) {
        if (isset($to_arg_functions[$index])) {
          $to_arg_function = $to_arg_functions[$index];
          $return = $to_arg_function(!empty($map[$index]) ? $map[$index] : '');
          if (!empty($map[$index]) || isset($return)) {
            $path_map[$index] = $return;
          }
          else {
            unset($path_map[$index]);
          }
        }
        else {
          $path_map[$index] = isset($map[$index]) ? $map[$index] : '';
        }
      }
      // We now have a real path regardless of operation, map it.
      if ($load_function) {
        $return = $load_function(isset($path_map[$index]) ? $path_map[$index] : '');
        // If callback returned an error or there is no callback, trigger 404.
        if ($return === FALSE) {
          return array(FALSE, FALSE, '');
        }
        $map[$index] = $return;
      }
    }
    if ($operation != MENU_HANDLE_REQUEST) {
      // Re-join the path with the new replacement value.
      $path = implode('/', $path_map);
    }
  }
  else {
    $path = $item->path;
  }

  // Determine access callback, which will decide whether or not the current user has
  // access to this path.
  $callback = $item->access_callback;
  // Check for a TRUE or FALSE value.
  if (is_numeric($callback)) {
    return array($callback, $map, $path);
  }
  $arguments = menu_unserialize($item->access_arguments, $map);
  // As call_user_func_array is quite slow and user_access is a very common
  // callback, it is worth making a special case for it.
  if ($callback == 'user_access') {
    $access = (count($arguments) == 1) ? user_access($arguments[0]) : user_access($arguments[0], $arguments[1]);
    return array($access, $map, $path);
  }
  return array(call_user_func_array($callback, $arguments), $map, $path);
}

/**
 * Returns a rendered menu tree.
 */
function menu_tree() {
  if ($item = menu_get_item()) {
    list(, $menu) = _menu_tree(db_query('SELECT * FROM {menu} WHERE pid IN ('. $item->parents .') AND visible = 1 ORDER BY vancode'));
    return $menu;
  }
}

function _menu_tree($result = NULL, $depth = 0, $link = array('link' => '', 'has_children' => FALSE)) {
  static $original_map;
  $remnant = array('link' => '', 'has_children' => FALSE);
  $tree = '';
  $map = arg(NULL);
  while ($item = db_fetch_object($result)) {
    list($access, , $path) = _menu_translate($item, $map, MENU_RENDER_LINK);
    if (!$access) {
      continue;
    }
    $menu_link = array('link' => l($item->title, $path), 'has_children' => $item->has_children);
    if ($item->depth > $depth) {
      list($remnant, $menu) = _menu_tree($result, $item->depth, $menu_link);
      $tree .= theme('menu_tree', $link, $menu);
      $link = $remnant;
      $remnant = array('link' => '', 'has_children' => FALSE);
    }
    elseif ($item->depth == $depth) {
      $tree .= theme('menu_link', $link);
      $link = $menu_link;
    }
    else {
      $remnant = $menu_link;
      break;
    }
  }
  if ($link['link']) {
    $tree .= theme('menu_link', $link);
  }
  return array($remnant, $tree);
}

/**
 * Generate the HTML for a menu tree.
 */
function theme_menu_tree($link, $tree) {
  $tree = '<ul class="menu">'. $tree .'</ul>';
  return $link['link'] ? theme('menu_link', $link, $tree) : $tree;
}

/**
 * Generate the HTML for a menu link.
 */
function theme_menu_link($link, $menu = '') {
  return '<li class="'. ($menu ? 'expanded' : (empty($link['has_children']) ? 'leaf': 'collapsed')) .'">'. $link['link'] . $menu .'</li>' . "\n";
}

function theme_menu_local_task($link, $active = FALSE) {
  return '<li '. ($active ? 'class="active" ' : ''). '>'. $link .'</li>';
}

/**
 * Returns the help associated with the active menu item.
 */
function menu_get_active_help() {
  $path = $_GET['q'];
  $output = '';
  $item = menu_get_item();

  if (!$item->access) {
    // Don't return help text for areas the user cannot access.
    return;
  }

  foreach (module_list() as $name) {
    if (module_hook($name, 'help')) {
      if ($temp = module_invoke($name, 'help', $path)) {
        $output .= $temp ."\n";
      }
      if (module_hook('help', 'page')) {
        if (arg(0) == "admin") {
          if (module_invoke($name, 'help', 'admin/help#'. arg(2)) && !empty($output)) {
            $output .= theme("more_help_link", url('admin/help/'. arg(2)));
          }
        }
      }
    }
  }
  return $output;
}

/**
 * Populate the database representation of the menu.
 */
function menu_rebuild() {
  $next = array();
  db_query('DELETE FROM {menu}');
  $menu = module_invoke_all('menu');
  foreach (module_implements('menu_alter') as $module) {
    $function = $module .'_menu_alter';
    $function($menu);
  }
  $mid = 1;
  // First pass: separate callbacks from pathes, making pathes ready for
  // matching. Calculate fitness, and fill some default values.
  foreach ($menu as $path => $item) {
    $parts = explode('/', $path, 6);
    $number_parts = count($parts);
    // We store the highest index of parts here to save some work in the fit
    // calculation loop.
    $slashes = $number_parts - 1;
    $fit = 0;
    $load_functions = array();
    $to_arg_functions = array();
    // extract functions
    foreach ($parts as $k => $part) {
      $match = FALSE;
      if (preg_match('/^%([a-z_]*)$/', $part, $matches)) {
        if (empty($matches[1])) {
          $match = TRUE;
          $load_functions[$k] = NULL;
          $to_arg_functions[$k] = NULL;
        }
        else {
          if (function_exists($matches[1] .'_to_arg')) {
            $to_arg_functions[$k] = $matches[1].'_to_arg';
            $load_functions[$k] = NULL;
            $match = TRUE;
          }
          if (function_exists($matches[1] .'_load')) {
            $load_functions[$k] = $matches[1] .'_load';
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
    $item['load_functions'] = empty($load_functions) ? '' : serialize($load_functions);
    $item['to_arg_functions'] = empty($to_arg_functions) ? '' : serialize($to_arg_functions);
    // If there is no %, it fits maximally.
    if (!$fit) {
      $fit = (1 << $number_parts) - 1;
      $move = FALSE;
    }
    else {
      $move = TRUE;
    }
    $item += array(
      'title' => '',
      'weight' => 0,
      'type' => MENU_NORMAL_ITEM,
      '_number_parts' => $number_parts,
      '_parts' => $parts,
      '_fit' => $fit,
      '_mid' => $mid++,
    );
    $item += array(
      '_visible' => (bool)($item['type'] & MENU_VISIBLE_IN_TREE),
      '_tab' => (bool)($item['type'] & MENU_IS_LOCAL_TASK),
    );
    if ($move) {
      $new_path = implode('/', $item['_parts']);
      unset($menu[$path]);
    }
    else {
      $new_path = $path;
    }
    $menu[$new_path] = $item;
  }
  // Second pass: find visible parents and prepare for sorting.
  foreach ($menu as $path => $item) {
    $item = &$menu[$path];
    $number_parts = $item['_number_parts'];
    $parents = array($item['_mid']);
    if ($item['_visible'] && isset($item['parent'])) {
      $parent_parts = explode('/', $item['parent'], 6);
      $slashes = count($parent_parts) - 1;
    }
    else {
      $parent_parts = $item['_parts'];
      $slashes = $number_parts -1;
    }
    $depth = 1;
    for ($i = $slashes; $i; $i--) {
      $parent_path = implode('/', array_slice($parent_parts, 0, $i));
      // We need to calculate depth to be able to sort. depth needs visibility.
      if (isset($menu[$parent_path])) {
        $parent = &$menu[$parent_path];
        if ($item['_visible'] && $parent['_visible']) {
          $parent['_has_children'] = 1;
          $depth++;
          $parents[] = $parent['_mid'];
          if (!isset($item['_pid'])) {
            $item['_pid'] = $parent['_mid'];
            $item['_visible_parent_path'] = $parent_path;
          }
        }
        unset($parent);
      }
    }
    $parents[] = 0;
    $parents = implode(',', array_reverse($parents));
    // Store variables and set defaults.
    $item += array(
      '_pid' => 0,
      '_depth' => $item['_visible'] ? $depth : $number_parts,
      '_parents' => $parents,
      '_has_children' => 0,
    );
    $sort[$path] = $item['_depth'] . sprintf('%05d', $item['weight']) . $item['title'];
    unset($item);
  }
  array_multisort($sort, $menu);
  // Third pass: calculate ancestors, vancode and store into the database.
  foreach ($menu as $path => $item) {
    $item = &$menu[$path];
    for ($i = $item['_number_parts'] - 1; $i; $i--) {
      $parent_path = implode('/', array_slice($item['_parts'], 0, $i));
      if (isset($menu[$parent_path])) {
        $parent = $menu[$parent_path];
        // If a callback is not found, we try to find the first parent that
        // has this callback. When found, its callback argument will also be
        // copied but only if there is none in the current item.
        foreach (array('access', 'page') as $type) {
          if (!isset($item["$type callback"]) && isset($parent["$type callback"])) {
            $item["$type callback"] = $parent["$type callback"];
            if (!isset($item["$type arguments"]) && isset($parent["$type arguments"])) {
              $item["$type arguments"] = $parent["$type arguments"];
            }
          }
        }
      }
    }
    if (!isset($item['access callback'])) {
      $menu[$path]['access callback'] = isset($item['access arguments']) ? 'user_access' : 0;
    }
    if (is_bool($item['access callback'])) {
      $item['access callback'] = intval($item['access callback']);
    }
    if ($item['_visible']) {
      $prefix = isset($item['_visible_parent_path']) ? $menu[$item['_visible_parent_path']]['_prefix'] : '';
      if (!isset($next[$prefix])) {
        $next[$prefix] = 0;
      }
      $vancode = $prefix . int2vancode($next[$prefix]++);
      $menu[$path]['_prefix'] = $vancode .'.';
    }
    else {
      $vancode = '';
    }
    if ($item['_tab']) {
      if (!isset($item['parent'])) {
        $item['parent'] = implode('/', array_slice($item['_parts'], 0, $item['_number_parts'] - 1));
      }
      else {
        $item['_depth'] = $item['parent'] ? $menu[$item['parent']]['_depth'] + 1 : 1;
      }
    }
    else {
      // Non-tab items specified the parent for visible links, and it's
      // stored in parents, parent stores the tab parent.
      $item['parent'] = $path;
    }
    $insert_item = $item + array(
      'access arguments' => array(),
      'access callback' => '',
      'page arguments' => array(),
      'page callback' => '',
    );
    db_query("INSERT INTO {menu} (
      mid, pid, path, load_functions, to_arg_functions,
      access_callback, access_arguments, page_callback, page_arguments, fit,
      number_parts, vancode, visible, parents, depth, has_children, tab, title, parent, type)
      VALUES (%d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', %d, '%s', %d, %d, %d, '%s', '%s', '%s')",
      $insert_item['_mid'], $insert_item['_pid'], $path,
      $insert_item['load_functions'], $insert_item['to_arg_functions'],
      $insert_item['access callback'], serialize($insert_item['access arguments']),
      $insert_item['page callback'], serialize($insert_item['page arguments']),
      $insert_item['_fit'], $insert_item['_number_parts'], $vancode .'+',
      $insert_item['_visible'], $insert_item['_parents'], $insert_item['_depth'],
      $insert_item['_has_children'], $item['_tab'], $insert_item['title'],
      $insert_item['parent'], $insert_item['type']);
    unset($item);
  }
}

function menu_map($arg, $function, $index, $default = FALSE) {
  $arg[$index] = is_numeric($arg[$index]) ? $function($arg[$index]) : $default;
  return $arg[$index] ? $arg : FALSE;
}

// Placeholders.
function menu_primary_links() {
}

function menu_secondary_links() {
}

/**
 * Collects the local tasks (tabs) for a given level.
 *
 * @param $level
     The level of tasks you ask for. Primary tasks are 0, secondary are 1...
 * @return
 *   An array of links to the tabs.
 */
function menu_local_tasks($level = 0) {
  static $tabs = array(), $parents = array(), $parents_done = array();
  if (empty($tabs)) {
    $router_item = menu_get_item();
    $map = arg(NULL);
    do {
      // Tabs are router items that have the same parent. If there is a new
      // parent, let's add it the queue.
      if (!empty($router_item->parent)) {
        $parents[] = $router_item->parent;
        // Do not add the same item twice.
        $router_item->parent = '';
      }
      $parent = array_shift($parents);
      // Do not process the same parent twice.
      if (isset($parents_done[$parent])) {
        continue;
      }
      // This loads all the tabs.
      $result = db_query("SELECT * FROM {menu} WHERE parent = '%s' AND tab = 1 ORDER BY vancode", $parent);
      $tabs_current = '';
      while ($item = db_fetch_object($result)) {
        // This call changes the path from for example user/% to user/123 and
        // also determines whether we are allowed to access it.
        list($access, , $path) = _menu_translate($item, $map, MENU_RENDER_LINK);
        if ($access) {
          $depth = $item->depth;
          $link = l($item->title, $path);
          // We check for the active tab.
          if ($item->path == $router_item->path || (!$router_item->tab && $item->type == MENU_DEFAULT_LOCAL_TASK) || $path == $_GET['q']) {
            $tabs_current .= theme('menu_local_task', $link, TRUE);
            // Let's try to find the router item one level up.
            $next_router_item = db_fetch_object(db_query("SELECT path, tab, parent FROM {menu} WHERE path = '%s'", $item->parent));
            // We will need to inspect one level down.
            $parents[] = $item->path;
          }
          else {
            $tabs_current .= theme('menu_local_task', $link);
          }
        }
      }
      // If there are tabs, let's add them
      if ($tabs_current) {
        $tabs[$depth] = $tabs_current;
      }
      $parents_done[$parent] = TRUE;
      if (isset($next_router_item)) {
        $router_item = $next_router_item;
      }
      unset($next_router_item);
    } while ($parents);
    // Sort by depth
    ksort($tabs);
    // Remove the depth, we are interested only in their relative placement.
    $tabs = array_values($tabs);
  }
  return isset($tabs[$level]) ? $tabs[$level] : array();
}

function menu_primary_local_tasks() {
  return menu_local_tasks();
}

function menu_secondary_local_tasks() {
  return menu_local_tasks(1);
}

function menu_set_active_item() {
}

function menu_set_location() {
}

function menu_get_active_breadcrumb() {
  return array(l(t('Home'), ''));
}

function menu_get_active_title() {
  $item = menu_get_item();
  return $item->title;
}
