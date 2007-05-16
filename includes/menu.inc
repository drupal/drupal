<?php
// $Id: menu.inc,v 1.166 2007/05/16 13:45:16 dries Exp $

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
 * then access is granted; if FALSE, then access is denied. Menu items may
 * omit this attribute to use the value provided by an ancestor item.
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
 * derived from the same hook_menu definitons, however you are free to
 * add more with menu_link_save().
 */

/**
 * @name Menu flags
 * @{
 * Flags for use in the "type" attribute of menu items.
 */

define('MENU_IS_ROOT', 0x0001);
define('MENU_VISIBLE_IN_TREE', 0x0002);
define('MENU_VISIBLE_IN_BREADCRUMB', 0x0004);
define('MENU_MODIFIED_BY_ADMIN', 0x0008);
define('MENU_MODIFIABLE_BY_ADMIN', 0x0010);
define('MENU_CREATED_BY_ADMIN', 0x0020);
define('MENU_IS_LOCAL_TASK', 0x0040);
define('MENU_EXPANDED', 0x0080);
define('MENU_LINKS_TO_PARENT', 0x00100);

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
 * Callbacks simply register a path so that the correct function is fired
 * when the URL is accessed. They are not shown in the menu.
 */
define('MENU_CALLBACK', MENU_VISIBLE_IN_BREADCRUMB);

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
 * @} End of "Menu operations."
 */

/**
 * @Name Menu tree parameters
 * @{
 * Menu tree
 */

 /**
 * The maximum number of path elements for a menu callback
 */
define('MENU_MAX_PARTS', 6);


/**
 * The maximum depth of a menu links tree - matches the number of p columns.
 */
define('MENU_MAX_DEPTH', 6);


/**
 * @} End of "Menu tree parameters".
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
 * Get the menu callback for the a path.
 *
 * @param $path
 *   A path, or NULL for the current path
 */
function menu_get_item($path = NULL) {
  static $items;
  if (!isset($path)) {
    $path = $_GET['q'];
  }
  if (!isset($items[$path])) {
    $original_map = arg(NULL, $path);
    $parts = array_slice($original_map, 0, MENU_MAX_PARTS);
    list($ancestors, $placeholders) = menu_get_ancestors($parts);

    if ($item = db_fetch_object(db_query_range('SELECT * FROM {menu_router} WHERE path IN ('. implode (',', $placeholders) .') ORDER BY fit DESC', $ancestors, 0, 1))) {

      $map = _menu_translate($item, $original_map);
      if ($map === FALSE) {
        $items[$path] = FALSE;
        return FALSE;
      }
      if ($item->access) {
        $item->map = $map;
        $item->page_arguments = array_merge(menu_unserialize($item->page_arguments, $map), array_slice($parts, $item->number_parts));
      }
    }
    $items[$path] = $item;
  }
  return drupal_clone($items[$path]);
}

/**
 * Execute the page callback associated with the current path
 */
function menu_execute_active_handler() {
  if ($item = menu_get_item()) {
    return $item->access ? call_user_func_array($item->page_callback, $item->page_arguments) : MENU_ACCESS_DENIED;
  }
  return MENU_NOT_FOUND;
}

/**
 * Loads objects into the map as defined in the $item->load_functions.
 *
 * @param $item
 *   A menu item object
 * @param $map
 *   An array of path arguments (ex: array('node', '5'))
 * @return
 *   Returns TRUE for success, FALSE if an object cannot be loaded
 */
function _menu_load_objects($item, &$map) {
  if ($item->load_functions) {
    $load_functions = unserialize($item->load_functions);
    $path_map = $map;
    foreach ($load_functions as $index => $function) {
      if ($function) {

        $return = $function(isset($path_map[$index]) ? $path_map[$index] : '');
        // If callback returned an error or there is no callback, trigger 404.
        if ($return === FALSE) {
          $item->access = FALSE;
          $map = FALSE;
          return FALSE;
        }
        $map[$index] = $return;
      }
    }
  }
  return TRUE;
}

/**
 * Check access to a menu item using the access callback
 *
 * @param $item
 *   A menu item object
 * @param $map
 *   An array of path arguments (ex: array('node', '5'))
 * @return
 *   $item->access becomes TRUE if the item is accessible, FALSE otherwise.
 */
function _menu_check_access(&$item, $map) {
  // Determine access callback, which will decide whether or not the current user has
  // access to this path.
  $callback = $item->access_callback;
  // Check for a TRUE or FALSE value.
  if (is_numeric($callback)) {
    $item->access = $callback;
  }
  else {
    $arguments = menu_unserialize($item->access_arguments, $map);
    // As call_user_func_array is quite slow and user_access is a very common
    // callback, it is worth making a special case for it.
    if ($callback == 'user_access') {
      $item->access = (count($arguments) == 1) ? user_access($arguments[0]) : user_access($arguments[0], $arguments[1]);
    }
    else {
      $item->access = call_user_func_array($callback, $arguments);
    }
  }
}

function _menu_item_localize(&$item) {
  // Translate the title to allow storage of English title strings
  // in the database, yet display of them in the language required
  // by the current user.
  $callback = $item->title_callback;
  // t() is a special case. Since it is used very close to all the time,
  // we handle it directly instead of using indirect, slower methods.
  if ($callback == 't') {
    if (empty($item->title_arguments)) {
      $item->title = t($item->title);
    }
    else {
      $item->title = t($item->title, unserialize($item->title_arguments));
    }
  }
  else {
    if (empty($item->title_arguments)) {
      $item->title = $callback($item->title);
    }
    else {
      $item->title = call_user_func_array($callback, unserialize($item->title_arguments));
    }
  }

  // Translate description, see the motivation above.
  if (!empty($item->description)) {
    $item->description = t($item->description);
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
 * @param $item
 *   A menu item object
 * @param $map
 *   An array of path arguments (ex: array('node', '5'))
 * @param $to_arg
 *   Execute $item->to_arg_functions or not. Use only if you want to render a
 *   path from the menu table, for example tabs.
 * @return
 *   Returns the map with objects loaded as defined in the
 *   $item->load_functions. $item->access becomes TRUE if the item is
 *   accessible, FALSE otherwise. $item->href is set according to the map.
 *   If an error occurs during calling the load_functions (like trying to load
 *   a non existing node) then this function return FALSE.
 */
function _menu_translate(&$item, $map, $to_arg = FALSE) {
  $path_map = $map;
  if (!_menu_load_objects($item, $map)) {
    // An error occurred loading an object.
    $item->access = FALSE;
    return FALSE;
  }
  if ($to_arg) {
    _menu_link_map_translate($path_map, $item->to_arg_functions);
  }

  // Generate the link path for the page request or local tasks.
  $link_map = explode('/', $item->path);
  for ($i = 0; $i < $item->number_parts; $i++) {
    if ($link_map[$i] == '%') {
      $link_map[$i] = $path_map[$i];
    }
  }
  $item->href = implode('/', $link_map);
  _menu_check_access($item, $map);

  _menu_item_localize($item);

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
 *   An array of helper function (ex: array(1 => 'node_load'))
 */
function _menu_link_map_translate(&$map, $to_arg_functions) {
  if ($to_arg_functions) {
    $to_arg_functions = unserialize($to_arg_functions);
    foreach ($to_arg_functions as $index => $function) {
      // Translate place-holders into real values.
      $arg = $function(!empty($map[$index]) ? $map[$index] : '');
      if (!empty($map[$index]) || isset($arg)) {
        $map[$index] = $arg;
      }
      else {
        unset($map[$index]);
      }
    }
  }
}

/**
 * This function is similar to _menu_translate() but does link-specific
 * preparation such as always calling to_arg functions
 *
 * @param $item
 *   A menu item object
 * @return
 *   Returns the map of path arguments with objects loaded as defined in the
 *   $item->load_functions.
 *   $item->access becomes TRUE if the item is accessible, FALSE otherwise.
 *   $item->href is altered if there is a to_arg function.
 */
function _menu_link_translate(&$item) {
  if ($item->external) {
    $item->access = 1;
    $map = array();
  }
  else {
    $map = explode('/', $item->href);
    _menu_link_map_translate($map, $item->to_arg_functions);
    $item->href = implode('/', $map);

    // Note- skip callbacks without real values for their arguments
    if (strpos($item->href, '%') !== FALSE) {
      $item->access = FALSE;
      return FALSE;
    }
    if (!_menu_load_objects($item, $map)) {
      // An error occured loading an object
      $item->access = FALSE;
      return FALSE;
    }
    // TODO: menu_tree may set this ahead of time for links to nodes
    if (!isset($item->access)) {
      _menu_check_access($item, $map);
    }
    // If the link title matches that of a router item, localize it.
    if (isset($item->title) && ($item->title == $item->link_title)) {
      _menu_item_localize($item);
      $item->link_title = $item->title;
    }
  }
  $item->options = unserialize($item->options);

  return $map;
}

/**
 * Returns a rendered menu tree. The tree is expanded based on the current
 * path and dynamic paths are also changed according to the defined to_arg
 * functions (for example the 'My account' link is changed from user/% to
 * a link with the current user's uid).
 *
 * @param $menu_name
 *   The name of the menu.
 * @return
 *   The rendered HTML of that menu on the current page.
 */
function menu_tree($menu_name = 'navigation') {
  static $menu_output = array();

  if (!isset($menu_output[$menu_name])) {
    $tree = menu_tree_data($menu_name);
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

  foreach ($tree as $data) {
    if (!$data['link']->hidden) {
      $link = theme('menu_item_link', $data['link']);
      if ($data['below']) {
        $output .= theme('menu_item', $link, $data['link']->has_children, menu_tree_output($data['below']));
      }
      else {
        $output .= theme('menu_item', $link, $data['link']->has_children);
      }
    }
  }
  return $output ? theme('menu_tree', $output) : '';
}

/**
 * Get the data structure representing a named menu tree, based on the current
 * page. The tree order is maintained by storing each parent in an invidual
 * field, see http://drupal.org/node/141866 for more.
 *
 * @param $menu_name
 *   The named menu links to return
 * @return
 *   An array of menu links, in the order they should be rendered. The array
 *   is a list of associative arrays -- these have two keys, link and below.
 *   link is a menu item, ready for theming as a link. Below represents the
 *   submenu below the link if there is one and it is a similar list that was
 *   described so far.
 */
function menu_tree_data($menu_name = 'navigation') {
  static $tree = array();

  if ($item = menu_get_item()) {
    if (!isset($tree[$menu_name])) {
      if ($item->access) {
        $parents = db_fetch_array(db_query("SELECT p1, p2, p3, p4, p5 FROM {menu_links} WHERE menu_name = '%s' AND href = '%s'", $menu_name, $item->href));
        // We may be on a local task that's not in the links
        // TODO how do we handle the case like a local task on a specific node in the menu?
        if (empty($parents)) {
          $parents = db_fetch_array(db_query("SELECT p1, p2, p3, p4, p5 FROM {menu_links} WHERE menu_name = '%s' AND href = '%s'", $menu_name, $item->tab_root));
        }
        $parents[] = '0';

        $args = $parents = array_unique($parents);
        $placeholders = implode(', ', array_fill(0, count($args), '%d'));
        $expanded = variable_get('menu_expanded', array());
        if (in_array($menu_name, $expanded)) {
          do {
            $result = db_query("SELECT mlid FROM {menu_links} WHERE expanded != 0 AND AND has_children != 0 AND menu_name = '%s' AND plid IN (". $placeholders .') AND mlid NOT IN ('. $placeholders .')', array_merge(array($menu_name), $args, $args));
            while ($item = db_fetch_array($result)) {
              $args[] = $item['mlid'];
            }
            $placeholders = implode(', ', array_fill(0, count($args), '%d'));
          } while (db_num_rows($result));
        }
        array_unshift($args, $menu_name);
      }
      // Show the root menu for access denied.
      else {
        $args = array('navigation', '0');
        $placeholders = '%d';
      }
      // LEFT JOIN since there is no match in {menu_router} for an external link.
      // No need to order by p6 - there is a sort by weight later.
      list(, $tree[$menu_name]) = _menu_tree_data(db_query("
        SELECT *, ml.weight + 50000 AS weight FROM {menu_links} ml LEFT JOIN {menu_router} m ON m.path = ml.router_path
        WHERE ml.menu_name = '%s' AND ml.plid IN (". $placeholders .")
        ORDER BY p1 ASC, p2 ASC, p3 ASC, p4 ASC, p5 ASC", $args), $parents);

      // TODO: cache_set() for the untranslated links
      // TODO: special case node links and access check via db_rewite_sql()
      // TODO: access check / _menu_link_translate on each here
    }
    return $tree[$menu_name];
  }
}


/**
 * Build the data representing a menu tree.
 *
 * The function is a bit complex because the rendering of an item depends on
 * the next menu item. So we are always rendering the element previously
 * processed not the current one.
 *
 * @param $result
 *   The database result.
 * @param $parents
 *   An array of the plid values that represent the path from the current page
 *   to the root of the menu tree.
 * @param $depth
 *   The depth of the current menu tree.
 * @param $previous_element
 *   The previous menu link in the current menu tree.
 * @return
 *   See menu_tree_data for a description of the data structure.
 */
function _menu_tree_data($result = NULL, $parents = array(), $depth = 1, $previous_element = '') {
  $remnant = NULL;
  $tree = array();
  while ($item = db_fetch_object($result)) {
    // Access check and handle dynamic path translation.
    // TODO - move this to the parent function, so the untranslated link data
    // can be cached.
    _menu_link_translate($item);
    if (!$item->access) {
      continue;
    }
    // We need to determine if we're on the path to root so we can later build
    // the correct active trail and breadcrumb.
    $item->path_to_root = in_array($item->mlid, $parents);
    // The weights are uniform 5 digits because of the 50000 offset in the
    // query. We add mlid at the end of the index to insure uniqueness.
    $index = $previous_element ? ($previous_element->weight .' '. $previous_element->title . $previous_element->mlid) : '';
    // The current item is the first in a new submenu.
    if ($item->depth > $depth) {
      // _menu_tree returns an item and the menu tree structure.
      list($item, $below) = _menu_tree_data($result, $parents, $item->depth, $item);
      $tree[$index] = array(
        'link' => $previous_element,
        'below' => $below,
      );
      // We need to fall back one level.
      if ($item->depth < $depth) {
        ksort($tree);
        return array($item, $tree);
      }
      // This will be the link to be output in the next iteration.
      $previous_element = $item;
    }
    // We are in the same menu. We render the previous element, $previous_element.
    elseif ($item->depth == $depth) {
      if ($previous_element) { // Only the first time
        $tree[$index] = array(
          'link' => $previous_element,
          'below' => '',
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
    $tree[$previous_element->weight .' '. $previous_element->title .' '. $previous_element->mlid] = array(
      'link' => $previous_element,
      'below' => '',
    );
  }
  ksort($tree);
  return array($remnant, $tree);
}

/**
 * Generate the HTML output for a single menu link.
 */
function theme_menu_item_link($link) {
  return l($link->link_title, $link->href, $link->options);
}

/**
 * Generate the HTML output for a menu tree
 */
function theme_menu_tree($tree) {
  return '<ul class="menu">'. $tree .'</ul>';
}

/**
 * Generate the HTML output for a menu item and submenu.
 */
function theme_menu_item($link, $has_children, $menu = '') {
  return '<li class="'. ($menu ? 'expanded' : ($has_children ? 'collapsed' : 'leaf')) .'">'. $link . $menu .'</li>'."\n";
}

function theme_menu_local_task($link, $active = FALSE) {
  return '<li '. ($active ? 'class="active" ' : '') .'>'. $link .'</li>';
}

/**
 * Returns the help associated with the active menu item.
 */
function menu_get_active_help() {

  $output = '';
  $item = menu_get_item();

  if (!$item || !$item->access) {
    // Don't return help text for areas the user cannot access.
    return;
  }
  $path = ($item->type == MENU_DEFAULT_LOCAL_TASK) ? $item->tab_parent : $item->path;

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
 * Build a list of named menus.
 */
function menu_get_names($reset = FALSE) {
  static $names;
  // TODO - use cache system to save this

  if ($reset || empty($names)) {
    $names = array();
    $result = db_query("SELECT DISTINCT(menu_name) FROM {menu_links} ORDER BY menu_name");
    while ($name = db_fetch_array($result)) {
      $names[] = $name['menu_name'];
    }
  }
  return $names;
}

function menu_primary_links() {
  $tree = menu_tree_data('primary links');
  return array();
}

function menu_secondary_links() {
  $tree = menu_tree_data('secondary links');
  return array();
}

/**
 * Collects the local tasks (tabs) for a given level.
 *
 * @param $level
 *   The level of tasks you ask for. Primary tasks are 0, secondary are 1.
 * @return
 *   An array of links to the tabs.
 */
function menu_local_tasks($level = 0) {
  static $tabs = array();

  if (empty($tabs)) {
    $router_item = menu_get_item();
    if (!$router_item || !$router_item->access) {
      return array();
    }
    // Get all tabs
    $result = db_query("SELECT * FROM {menu_router} WHERE tab_root = '%s' AND tab_parent != '' ORDER BY weight, title", $router_item->tab_root);
    $map = arg();
    $children = array();
    $tab_parent = array();

    while ($item = db_fetch_object($result)) {
      $children[$item->tab_parent][$item->path] = $item;
      $tab_parent[$item->path] = $item->tab_parent;
    }

    // Find all tabs below the current path
    $path = $router_item->path;
    while (isset($children[$path])) {
      $tabs_current = '';
      $next_path = '';
      foreach ($children[$path] as $item) {
         _menu_translate($item, $map, TRUE);
        if ($item->access) {
          $link = l($item->title, $item->href); // TODO options?
          // The default task is always active.
          if ($item->type == MENU_DEFAULT_LOCAL_TASK) {
            $tabs_current .= theme('menu_local_task', $link, TRUE);
            $next_path = $item->path;
          }
          else {
            $tabs_current .= theme('menu_local_task', $link);
          }
        }
      }
      $path = $next_path;
      $tabs[$item->number_parts] = $tabs_current;
    }

    // Find all tabs at the same level or above the current one
    $parent = $router_item->tab_parent;
    $path = $router_item->path;
    $current = $router_item;
    while (isset($children[$parent])) {
      $tabs_current = '';
      $next_path = '';
      $next_parent = '';
      foreach ($children[$parent] as $item) {
         _menu_translate($item, $map, TRUE);
        if ($item->access) {
          $link = l($item->title, $item->href); // TODO options?
          // We check for the active tab.
          if ($item->path == $path) {
            $tabs_current .= theme('menu_local_task', $link, TRUE);
            $next_path = $item->tab_parent;
            if (isset($tab_parent[$next_path])) {
              $next_parent = $tab_parent[$next_path];
            }
          }
          else {
            $tabs_current .= theme('menu_local_task', $link);
          }
        }
      }
      $path = $next_path;
      $parent = $next_parent;
      $tabs[$item->number_parts] = $tabs_current;
    }
    // Sort by depth
    ksort($tabs);
    // Remove the depth, we are interested only in their relative placement.
    $tabs = array_values($tabs);
  }
  return isset($tabs[$level]) ? $tabs[$level] : array();
}

function menu_primary_local_tasks() {
  return menu_local_tasks(0);
}

function menu_secondary_local_tasks() {
  return menu_local_tasks(1);
}

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

function menu_get_active_menu_name() {
  return menu_set_active_menu_name();
}

function menu_set_active_item() {
}

function menu_set_active_trail($new_trail = NULL) {
  static $trail;

  if (isset($new_trail)) {
    $trail = $new_trail;
  }
  elseif (!isset($trail)) {
    $trail = array();
    $h = array('link_title' => t('Home'), 'href' => '<front>', 'options' => array(), 'type' => 0, 'title' => '');
    $trail[] = (object)$h;
    $item = menu_get_item();
    // We are on a tab.
    if ($item->tab_parent) {
      $href = $item->tab_root;
    }
    else {
      $href = $item->href;
    }
    $tree = menu_tree_data(menu_get_active_menu_name());
    $curr = array_shift($tree);

    while ($curr) {
      if ($curr['link']->href == $href){
        $trail[] = $curr['link'];
        $curr = FALSE;
      }
      else {
        if ($curr['below'] && $curr['link']->path_to_root) {
          $trail[] = $curr['link'];
          $tree = $curr['below'];
        }
        $curr = array_shift($tree);
      }
    }
  }
  return $trail;
}

function menu_get_active_trail() {
  return menu_set_active_trail();
}

function menu_set_location() {
}

function menu_get_active_breadcrumb() {
  $breadcrumb = array();
  $item = menu_get_item();
  if ($item && $item->access) {
    $active_trail = menu_get_active_trail();

    foreach ($active_trail as $parent) {
      $breadcrumb[] = l($parent->link_title, $parent->href, $parent->options);
    }
    $end = end($active_trail);

    // Don't show a link to the current page in the breadcrumb trail.
    if ($item->href == $end->href || ($item->type == MENU_DEFAULT_LOCAL_TASK && $end->href != '<front>')) {
      array_pop($breadcrumb);
    }
  }
  return $breadcrumb;
}

function menu_get_active_title() {
  $active_trail = menu_get_active_trail();

  foreach (array_reverse($active_trail) as $item) {
    if (!(bool)($item->type & MENU_IS_LOCAL_TASK)) {
      return $item->title;
    }
  }
}

/**
 * Get a menu item by its mlid, access checked and link translated for
 * rendering.
 *
 * @param $mlid
 *   The mlid of the menu item.
 * @return
 *   A menu object, with $item->access filled and link translated for
 *   rendering.
 */
function menu_get_item_by_mlid($mlid) {
  if ($item = db_fetch_object(db_query("SELECT * FROM {menu_links} ml LEFT JOIN {menu_router} m ON m.path = ml.router_path WHERE mlid = %d", $mlid))) {
    _menu_link_translate($item);
    if ($item->access) {
      return $item;
    }
  }
  return FALSE;
}

/**
 * Returns the rendered local tasks. The default implementation renders
 * them as tabs.
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

function menu_cache_clear($menu_name = 'navigation') {
  // TODO: starting stub.  This will be called whenever an item is added to or
  // moved from a named menu

}

/**
 * This should be called any time broad changes might have been made to the
 * router items or menu links.
 */
function menu_cache_clear_all() {
  cache_clear_all('*', 'menu_links', TRUE);
  cache_clear_all('*', 'menu_router', TRUE);
}

/**
 * Populate the database representation of the {menu_router} table (router items)
 * and the navigation menu in the {menu_links} table.
 */
function menu_rebuild() {
  menu_cache_clear_all();
  $menu = menu_router_build(TRUE);
  _menu_navigation_links_rebuild($menu);
}

/**
 * Collect, alter and store the menu definitions.
 */
function menu_router_build() {
  db_query('DELETE FROM {menu_router}');
  $callbacks = module_invoke_all('menu');
  // Alter the menu as defined in modules, keys are like user/%user.
  drupal_alter('menu', $callbacks);
  $menu = _menu_router_build($callbacks);
  return $menu;
}

function _menu_navigation_links_rebuild($menu) {
  // Add normal and suggested items as links.
  $menu_links = array();
  foreach ($menu as $path => $item) {
    if ($item['type'] == MENU_CALLBACK || $item['type'] == MENU_SUGGESTED_ITEM) {
      $item['hidden'] = $item['type'];
    }
    $item += array(
      'menu name' => 'navigation',
      'link_title' => $item['title'],
      'href' => $path,
      'module' => 'system',
      'hidden' => 0,
    );
    // We add nonexisting items.
    if ($item['_visible'] && !db_result(db_query("SELECT COUNT(*) FROM {menu_links} WHERE menu_name = '%s' AND href = '%s'", $item['menu name'], $item['href']))) {
      $menu_links[$path] = $item;
      $sort[$path] = $item['_number_parts'];
    }
  }
  if ($menu_links) {
    // Make sure no child comes before its parent.
    array_multisort($sort, SORT_NUMERIC, $menu_links);

    foreach ($menu_links as $item) {
      menu_link_save($item, $menu);
    }
  }
  $placeholders = implode(', ', array_fill(0, count($menu), "'%s'"));
  // Remove items if their router path does not exist any more.
  db_query('DELETE FROM {menu_links} WHERE router_path NOT IN ('. $placeholders .')', array_keys($menu));
}

/**
 * Save a menu link.
 *
 * @param $item
 *   An array representing a menu link item. The only mandatory keys are href
 *   and link_title. Possible keys are
 *     menu name   default is navigation
 *     weight      default is 0
 *     expanded    whether the item is expanded.
 *     options     An array of options, @see l for more.
 *     mlid        If it's an existing item, this comes from the database.
 *                 Never set by hand.
 *     plid        The mlid of the parent.
 *     router_path The path of the relevant router item.
 */
function menu_link_save(&$item, $_menu = NULL) {
  static $menu;

  if (isset($_menu)) {
    $menu = $_menu;
  }
  elseif (!isset($menu)) {
    $menu = menu_router_build();
  }

  drupal_alter('menu_link', $item, $menu);

  $item['_external'] = menu_path_is_external($item['href']);
  // Load defaults.
  $item += array(
    'menu name' => 'navigation',
    'weight' => 0,
    'link_title' => '',
    'hidden' => 0,
    'has_children' => 0,
    'expanded' => 0,
    'options' => empty($item['description']) ? array() : array('attributes' => array('title' => $item['description'])),
  );
  $existing_item = array();
  if (isset($item['mlid'])) {
    $existing_item = db_fetch_array(db_query("SELECT * FROM {menu_links} WHERE mlid = %d", $item['mlid']));
  }
  else {
    $existing_item = db_fetch_array(db_query("SELECT * FROM {menu_links} WHERE menu_name = '%s' AND href = '%s'", $item['menu name'], $item['href']));
  }

  if (empty($existing_item)) {
    $item['mlid'] = db_next_id('{menu_links}_mlid');
  }

  $menu_name = $item['menu name'];
  $new_path = !$existing_item || ($existing_item['href'] != $item['href']);

  // Find the parent.
  if (isset($item['plid'])) {
    $parent = db_fetch_array(db_query("SELECT * FROM {menu_links} WHERE mlid = %d", $item['plid']));
  }
  else { //
    $parent_path = $item['href'];
    do {
      $parent_path = substr($parent_path, 0, strrpos($parent_path, '/'));
      $parent = db_fetch_array(db_query("SELECT * FROM {menu_links} WHERE menu_name = '%s' AND href = '%s'", $menu_name, $parent_path));
    } while ($parent === FALSE && $parent_path);
  }
  // Menu callbacks need to be in the links table for breadcrumbs, but can't
  // be parents if they are generated directly from a router item
  if (empty($parent['mlid']) || $parent['hidden'] == MENU_CALLBACK) {
    $item['plid'] =  0;
  }
  else {
    $item['plid'] = $parent['mlid'];
  }

  if (!$item['plid']) {
    $item['p1'] = $item['mlid'];
    $item['p2'] = $item['p3'] = $item['p4'] = $item['p5'] = $item['p6'] = 0;
    $item['depth'] = 1;
  }
  else {
    // Cannot add beyond the maximum depth.
    if ($parent['depth'] >= (MENU_MAX_DEPTH)) {
      return FALSE;
    }
    $item['depth'] = $parent['depth'] + 1;
    _menu_parents_copy($item, $parent);
    $item['p'. $item['depth']] = $item['mlid'];
  }

  if ($item['plid'] != $existing_item['plid']) {

    // TODO: UPDATE the parents of the children of the current item
    // TODO: check the has_children status of the previous parent
  }
  // Find the callback.
  if (empty($item['router_path']) || $new_path) {
    if ($item['_external']) {
      $item['router_path'] = '';
    }
    else {
      // Find the router path which will serve this path.
      $item['parts'] = explode('/', $item['href'], MENU_MAX_PARTS);
      $item['router_path'] = $item['href'];
      if (!isset($menu[$item['router_path']])) {
        list($ancestors) = menu_get_ancestors($item['parts']);
        while ($ancestors && (!isset($menu[$item['router_path']]))) {
          $item['router_path'] = array_pop($ancestors);
        }
      }
      if (empty($item['router_path'])) {
        return FALSE;
      }
    }
  }
  if (!empty($existing_item)) {
    db_query("UPDATE {menu_links} SET menu_name = '%s', plid = %d, href = '%s',
      router_path = '%s', hidden = %d, external = %d, has_children = %d,
      expanded = %d, weight = %d,  depth = %d,
      p1 = %d, p2 = %d, p3 = %d, p4 = %d, p5 = %d, p6 = %d,
      module = '%s', link_title = '%s', options = '%s' WHERE mlid = %d",
      $item['menu name'], $item['plid'], $item['href'],
      $item['router_path'], $item['hidden'], $item['_external'], $item['has_children'],
      $item['expanded'],$item['weight'],  $item['depth'],
      $item['p1'], $item['p2'], $item['p3'], $item['p4'], $item['p5'], $item['p6'],
      $item['module'],  $item['link_title'], serialize($item['options']), $item['mlid']);
  }
  else {
    db_query("INSERT INTO {menu_links} (
      menu_name, mlid, plid, href,
      router_path, hidden, external, has_children,
      expanded, weight, depth,
      p1, p2, p3, p4, p5, p6,
      module, link_title, options) VALUES (
      '%s', %d, %d, '%s',
      '%s', %d, %d, %d,
      %d, %d, %d,
      %d, %d, %d, %d, %d, %d,
      '%s', '%s', '%s')",
      $item['menu name'], $item['mlid'], $item['plid'], $item['href'],
      $item['router_path'], $item['hidden'], $item['_external'], $item['has_children'],
      $item['expanded'],$item['weight'],  $item['depth'],
      $item['p1'], $item['p2'], $item['p3'], $item['p4'], $item['p5'], $item['p6'],
      $item['module'],  $item['link_title'], serialize($item['options']));
 }

  if ($item['plid'] && !$item['hidden']) {
    db_query("UPDATE {menu_links} SET has_children = 1 WHERE mlid = %d", $item['plid']);
  }

  // Keep track of which menus have expanded items
  $names = array();
  $result = db_query("SELECT menu_name FROM {menu_links} WHERE expanded != 0 GROUP BY menu_name");
  while ($n = db_fetch_array($result)) {
    $names[] = $n['menu_name'];
  }
  variable_set('menu_expanded', $names);
  return TRUE;
}

function _menu_parents_copy(&$dest, $source, $offset = 0){
  $i = 1 + $offset;
  $depth = 0;
  for ($j = 1; $i <= MENU_MAX_DEPTH; $i++) {
    $dest['p'. $i] = $source['p'. $j];
    $j++;
  }
}

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
    // extract functions
    foreach ($parts as $k => $part) {
      $match = FALSE;
      if (preg_match('/^%([a-z_]*)$/', $part, $matches)) {
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
    if ($fit) {
      $move = TRUE;
    }
    else {
      // If there is no %, it fits maximally.
      $fit = (1 << $number_parts) - 1;
    }
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

  // Apply inheritance rules.
  foreach ($menu as $path => $v) {
    $item = &$menu[$path];
    if (!isset($item['access callback']) && isset($item['access arguments'])) {
      $item['access callback'] = 'user_access'; // Default callback
    }
    if (!$item['_tab']) {
      // Non-tab items
      $item['tab_parent'] = '';
      $item['tab_root'] = $path;
    }
    for ($i = $item['_number_parts'] - 1; $i; $i--) {
      $parent_path = implode('/', array_slice($item['_parts'], 0, $i));
      if (isset($menu[$parent_path])) {

        $parent = $menu[$parent_path];

        if (!isset($item['tab_parent'])) {
          // parent stores the parent of the path.
          $item['tab_parent'] = $parent_path;
        }
        if (!isset($item['tab_root']) && !$parent['_tab']) {
          $item['tab_root'] = $parent_path;
        }
        // If a callback is not found, we try to find the first parent that
        // has a callback.
        if (!isset($item['access callback']) && isset($parent['access callback'])) {
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
        }
      }
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
    );
    db_query("INSERT INTO {menu_router}
      (path, load_functions, to_arg_functions, access_callback,
      access_arguments, page_callback, page_arguments, fit,
      number_parts, tab_parent, tab_root,
      title, title_callback, title_arguments,
      type, block_callback, description, position, weight)
      VALUES ('%s', '%s', '%s', '%s',
      '%s', '%s', '%s', %d,
      %d, '%s', '%s',
      '%s', '%s', '%s',
      %d, '%s', '%s', '%s', %d)",
      $path, $item['load_functions'], $item['to_arg_functions'], $item['access callback'],
      serialize($item['access arguments']), $item['page callback'], serialize($item['page arguments']), $item['_fit'],
      $item['_number_parts'], $item['tab_parent'], $item['tab_root'],
      $item['title'], $item['title callback'], serialize($item['title arguments']),
      $item['type'], $item['block callback'], $item['description'], $item['position'], $item['weight']);
  }
  return $menu;
}

function menu_path_is_external($path) {
  $colonpos = strpos($path, ':');
  return $colonpos !== FALSE && !preg_match('![/?#]!', substr($path, 0, $colonpos)) && filter_xss_bad_protocol($path, FALSE) == check_plain($path);
}
