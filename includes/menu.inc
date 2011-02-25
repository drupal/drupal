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
 * Return the menu data structure.
 *
 * The returned structure contains much information that is useful only
 * internally in the menu system. External modules are likely to need only
 * the ['visible'] element of the returned array. All menu items that are
 * accessible to the current user and not hidden will be present here, so
 * modules and themes can use this structure to build their own representations
 * of the menu.
 *
 * $menu['visible'] will contain an associative array, the keys of which
 * are menu IDs. The values of this array are themselves associative arrays,
 * with the following key-value pairs defined:
 * - 'title' - The displayed title of the menu or menu item. It will already
 *   have been translated by the locale system.
 * - 'description' - The description (link title attribute) of the menu item.
 *   It will already have been translated by the locale system.
 * - 'path' - The Drupal path to the menu item. A link to a particular item
 *   can thus be constructed with
 *   l($item['title'], $item['path'], array('title' => $item['description'])).
 * - 'children' - A linear list of the menu ID's of this item's children.
 *
 * Menu ID 0 is the "root" of the menu. The children of this item are the
 * menus themselves (they will have no associated path). Menu ID 1 will
 * always be one of these children; it is the default "Navigation" menu.
 */
function menu_get_menu() {
  global $_menu;
  global $user;
  global $locale;

  if (!isset($_menu['items'])) {
    // _menu_build() may indirectly call this function, so prevent infinite loops.
    $_menu['items'] = array();

    $cid = "menu:$user->uid:$locale";
    if ($cached = cache_get($cid)) {
      $_menu = unserialize($cached->data);
    }
    else {
      _menu_build();
      // Cache the menu structure for this user, to expire after one day.
      cache_set($cid, serialize($_menu), time() + (60 * 60 * 24));
    }

    // Make sure items that cannot be cached are added.
    _menu_append_contextual_items();

    // Reset the cached $menu in menu_get_item().
    menu_get_item(NULL, NULL, TRUE);
  }

  return $_menu;
}

/**
 * Return the local task tree.
 *
 * Unlike the rest of the menu structure, the local task tree cannot be cached
 * nor determined too early in the page request, because the user's current
 * location may be changed by a menu_set_location() call, and the tasks shown
 * (just as the breadcrumb trail) need to reflect the changed location.
 */
function menu_get_local_tasks() {
  global $_menu;

  // Don't cache the local task tree, as it varies by location and tasks are
  // allowed to be dynamically determined.
  if (!isset($_menu['local tasks'])) {
    // _menu_build_local_tasks() may indirectly call this function, so prevent
    // infinite loops.
    $_menu['local tasks'] = array();
    $pid = menu_get_active_nontask_item();
    if (!_menu_build_local_tasks($pid)) {
      // If the build returned FALSE, the tasks need not be displayed.
      $_menu['local tasks'][$pid]['children'] = array();
    }
  }

  return $_menu['local tasks'];
}

/**
 * Retrieves the menu item specified by $mid, or by $path if $mid is not given.
 *
 * @param $mid
 *   The menu ID of the menu item to retrieve.
 * @param $path
 *   The internal path of the menu item to retrieve. Defaults to NULL. Only
 *   used if $mid is not set.
 * @param $reset
 *   Optional flag that resets the static variable cache of the menu tree, if
 *   set to TRUE. Default is FALSE.
 *
 * @return
 *   The menu item found in the site menu, or an empty array if none could be
 *   found.
 */
function menu_get_item($mid, $path = NULL, $reset = FALSE) {
  static $menu;

  if (!isset($menu) || $reset) {
    $menu = menu_get_menu();
  }

  if (isset($mid)) {
    return $menu['items'][$mid];
  }

  if (isset($path)) {
    return $menu['items'][$menu['path index'][$path]];
  }

  return array();
}

/**
 * Retrieves the menu ID and title of all root menus.
 *
 * @return
 *   Array containing all menus (but not menu items), in the form mid => title.
 */
function menu_get_root_menus() {
  $menu = menu_get_menu();
  $root_menus = array();

  foreach ($menu['items'][0]['children'] as $mid) {
    $root_menus[$mid] = $menu['items'][$mid]['title'];
  }

  return $root_menus;
}

/**
 * Change the current menu location of the user.
 *
 * Frequently, modules may want to make a page or node act as if it were
 * in the menu tree somewhere, even though it was not registered in a
 * hook_menu() implementation. If the administrator has rearranged the menu,
 * the newly set location should respect this in the breadcrumb trail and
 * expanded/collapsed status of menu items in the tree. This function
 * allows this behavior.
 *
 * @param $location
 *   An array specifying a complete or partial breadcrumb trail for the
 *   new location, in the same format as the return value of hook_menu().
 *   The last element of this array should be the new location itself.
 *
 * This function will set the new breadcrumb trail to the passed-in value,
 * but if any elements of this trail are visible in the site tree, the
 * trail will be "spliced in" to the existing site navigation at that point.
 */
function menu_set_location($location) {
  global $_menu;
  $temp_id = min(array_keys($_menu['items'])) - 1;
  $prev_id = 0;

  // Don't allow this function to change the actual current path, just the
  // position in the menu tree.
  $location[count($location) - 1]['path'] = $_GET['q'];

  foreach (array_reverse($location) as $item) {
    if (isset($_menu['path index'][$item['path']])) {
      $mid = $_menu['path index'][$item['path']];
      if (isset($_menu['visible'][$mid])) {
        // Splice in the breadcrumb at this location.
        if ($prev_id) {
          $_menu['items'][$prev_id]['pid'] = $mid;
        }
        $prev_id = 0;
        break;
      }
      else {
        // A hidden item; show it, but only temporarily.
        $_menu['items'][$mid]['type'] |= MENU_VISIBLE_IN_BREADCRUMB;
        if ($prev_id) {
          $_menu['items'][$prev_id]['pid'] = $mid;
        }
        $prev_id = $mid;
      }
    }
    else {
      $item['type'] |= MENU_VISIBLE_IN_BREADCRUMB;
      if ($prev_id) {
        $_menu['items'][$prev_id]['pid'] = $temp_id;
      }
      $_menu['items'][$temp_id] = $item;
      $_menu['path index'][$item['path']] = $temp_id;

      $prev_id = $temp_id;
      $temp_id--;
    }
  }

  if ($prev_id) {
    // Didn't find a home, so attach this to the main navigation menu.
    $_menu['items'][$prev_id]['pid'] = 1;
  }

  $final_item = array_pop($location);
  menu_set_active_item($final_item['path']);
}

/**
 * Execute the handler associated with the active menu item.
 *
 * This is called early in the page request. The active menu item is at
 * this point determined exclusively by the URL. The handler that is called
 * here may, as a side effect, change the active menu item so that later
 * menu functions (that display the menus and breadcrumbs, for example)
 * act as if the user were in a different location on the site.
 */
function menu_execute_active_handler() {
  if (_menu_site_is_offline()) {
    return MENU_SITE_OFFLINE;
  }

  $menu = menu_get_menu();

  // Determine the menu item containing the callback.
  $path = $_GET['q'];
  while ($path && !isset($menu['callbacks'][$path])) {
    $path = substr($path, 0, strrpos($path, '/'));
  }

  if (!isset($menu['callbacks'][$path])) {
    return MENU_NOT_FOUND;
  }

  if (!function_exists($menu['callbacks'][$path]['callback'])) {
    return MENU_NOT_FOUND;
  }

  if (!_menu_item_is_accessible(menu_get_active_item())) {
    return MENU_ACCESS_DENIED;
  }

  // We found one, and are allowed to execute it.
  $arguments = isset($menu['callbacks'][$path]['callback arguments']) ? $menu['callbacks'][$path]['callback arguments'] : array();
  $arg = substr($_GET['q'], strlen($path) + 1);
  if (strlen($arg)) {
    $arguments = array_merge($arguments, explode('/', $arg));
  }

  return call_user_func_array($menu['callbacks'][$path]['callback'], $arguments);
}

/**
 * Returns the ID of the active menu item.
 */
function menu_get_active_item() {
  return menu_set_active_item();
}

/**
 * Sets the path of the active menu item.
 */
function menu_set_active_item($path = NULL) {
  static $stored_mid;

  if (!isset($stored_mid) || isset($path)) {
    $menu = menu_get_menu();
    if (!isset($path)) {
      $path = $_GET['q'];
    }
    else {
      $_GET['q'] = $path;
    }

    while ($path && !isset($menu['path index'][$path])) {
      $path = substr($path, 0, strrpos($path, '/'));
    }
    $stored_mid = isset($menu['path index'][$path]) ? $menu['path index'][$path] : 0;

    // Search for default local tasks to activate instead of this item.
    $continue = TRUE;
    while ($continue) {
      $continue = FALSE;
      if (isset($menu['items'][$stored_mid]['children'])) {
        foreach ($menu['items'][$stored_mid]['children'] as $cid) {
          if ($menu['items'][$cid]['type'] & MENU_LINKS_TO_PARENT) {
            $stored_mid = $cid;
            $continue = TRUE;
          }
        }
      }
    }

    // Reset the cached $menu in menu_get_item().
    menu_get_item(NULL, NULL, TRUE);
  }

  return $stored_mid;
}

/**
 * Returns the ID of the current menu item or, if the current item is a
 * local task, the menu item to which this task is attached.
 */
function menu_get_active_nontask_item() {
  $mid = menu_get_active_item();

  // Find the first non-task item:
  while ($mid) {
    $item = menu_get_item($mid);

    if (!($item['type'] & MENU_IS_LOCAL_TASK)) {
      return $mid;
    }

    $mid = $item['pid'];
  }
}

/**
 * Returns the title of the active menu item.
 */
function menu_get_active_title() {
  if ($mid = menu_get_active_nontask_item()) {
    $item = menu_get_item($mid);
    return $item['title'];
  }
}

/**
 * Returns the help associated with the active menu item.
 */
function menu_get_active_help() {
  $path = $_GET['q'];
  $output = '';

  if (!_menu_item_is_accessible(menu_get_active_item())) {
    // Don't return help text for areas the user cannot access.
    return;
  }

  foreach (module_list() as $name) {
    if (module_hook($name, 'help')) {
      if ($temp = module_invoke($name, 'help', $path)) {
        $output .= $temp . "\n";
      }
      if (module_hook('help', 'page')) {
        if (substr($path, 0, 6) == "admin/") {
          if (module_invoke($name, 'help', 'admin/help#' . substr($path, 6))) {
            $output .= theme("more_help_link", url('admin/help/' . substr($path, 6)));
          }
        }
      }
    }
  }
  return $output;
}

/**
 * Returns an array of rendered menu items in the active breadcrumb trail.
 */
function menu_get_active_breadcrumb() {

  // No breadcrumb for the front page.
  if (drupal_is_front_page()) {
    return array();
  }

  // We do *not* want to use "variable_get('site_frontpage', 'node)" here
  // as that will create the link '/node'. This is unsightly and creates
  // a second URL for the homepage ('/' *and* '/node').
  $links[] = l(t('Home'), '');

  $trail = _menu_get_active_trail();
  foreach ($trail as $mid) {
    $item = menu_get_item($mid);
    if ($item['type'] & MENU_VISIBLE_IN_BREADCRUMB) {
      $links[] = menu_item_link($mid);
    }
  }

  // The last item in the trail is the page title; don't display it here.
  array_pop($links);

  return $links;
}

/**
 * Returns true when the menu item is in the active trail.
 */
function menu_in_active_trail($mid) {
  $trail = _menu_get_active_trail();

  return in_array($mid, $trail);
}

/**
 * Returns true when the menu item is in the active trail within a
 * specific subsection of the menu tree.
 *
 * @param $mid
 *   The menu item being considered.
 * @param $pid
 *   The root of the subsection of the menu tree in which to look.
 */
function menu_in_active_trail_in_submenu($mid, $pid) {
  $trail = _menu_get_active_trail_in_submenu($pid);

  if (!$trail) {
    return FALSE;
  }

  return in_array($mid, $trail);
}

/**
 * Populate the database representation of the menu.
 *
 * This need only be called at the start of pages that modify the menu.
 */
function menu_rebuild() {
  // Clear the page cache, so that changed menus are reflected for anonymous users.
  cache_clear_all();
  // Also clear the menu cache.
  cache_clear_all('menu:', TRUE);

  _menu_build();

  if (module_exist('menu')) {
    $menu = menu_get_menu();

    // Fill a queue of new menu items which are modifiable.
    $new_items = array();
    foreach ($menu['items'] as $mid => $item) {
      if ($mid < 0 && ($item['type'] & MENU_MODIFIABLE_BY_ADMIN)) {
        $new_items[$mid] = $item;
      }
    }

    $old_count = -1;
    // Save the new items updating the pids in each iteration
    while (($c = count($new_items)) && ($c != $old_count)) {
      $old_count = count($new_items);
      foreach($new_items as $mid => $item) {
        // If the item has a valid parent, save it
        if ($item['pid'] >= 0) {
          // The new menu ID gets passed back by reference as $item['mid']
          menu_save_item($item);
          // Fix parent IDs for the children of the menu item just saved
          if ($item['children']) {
            foreach ($item['children'] as $child) {
              if (isset($new_items[$child])) {
                $new_items[$child]['pid'] = $item['mid'];
              }
            }
          }
          // remove the item
          unset($new_items[$mid]);
        }
      }
    }
    // Rebuild the menu to account for the changes.
    _menu_build();
  }

  // Reset the cached $menu in menu_get_item().
  menu_get_item(NULL, NULL, TRUE);

}

/**
 * Generate the HTML for a menu tree.
 *
 * @param $pid
 *   The parent id of the menu.
 *
 * @ingroup themeable
 */
function theme_menu_tree($pid = 1) {
  if ($tree = menu_tree($pid)) {
    return "\n<ul class=\"menu\">\n". $tree ."\n</ul>\n";
  }
}

/**
 * Returns a rendered menu tree.
 *
 * @param $pid
 *   The parent id of the menu.
 */
function menu_tree($pid = 1) {
  $menu = menu_get_menu();
  $output = '';

  if (isset($menu['visible'][$pid]) && $menu['visible'][$pid]['children']) {
    foreach ($menu['visible'][$pid]['children'] as $mid) {
      $type = isset($menu['visible'][$mid]['type']) ? $menu['visible'][$mid]['type'] : NULL;
      $children = isset($menu['visible'][$mid]['children']) ? $menu['visible'][$mid]['children'] : NULL;
      $output .= theme('menu_item', $mid, menu_in_active_trail($mid) || ($type & MENU_EXPANDED) ? theme('menu_tree', $mid) : '', count($children) == 0);
    }
  }

  return $output;
}

/**
 * Generate the HTML output for a single menu item.
 *
 * @param $mid
 *   The menu id of the item.
 * @param $children
 *   A string containing any rendered child items of this menu.
 * @param $leaf
 *   A boolean indicating whether this menu item is a leaf.
 *
 * @ingroup themeable
 */
function theme_menu_item($mid, $children = '', $leaf = TRUE) {
  return '<li class="'. ($leaf ? 'leaf' : ($children ? 'expanded' : 'collapsed')) .'">'. menu_item_link($mid) . $children ."</li>\n";
}

/**
 * Generate the HTML representing a given menu item ID.
 *
 * @param $item
 *   The menu item to render.
 * @param $link_item
 *   The menu item which should be used to find the correct path.
 *
 * @ingroup themeable
 */
function theme_menu_item_link($item, $link_item) {
  return l($item['title'], $link_item['path'], !empty($item['description']) ? array('title' => $item['description']) : array());
}

/**
 * Returns the rendered link to a menu item.
 *
 * @param $mid
 *   The menu item id to render.
 */
function menu_item_link($mid) {
  $item = menu_get_item($mid);
  $link_item = $item;

  while ($link_item['type'] & MENU_LINKS_TO_PARENT) {
    $link_item = menu_get_item($link_item['pid']);
  }

  return theme('menu_item_link', $item, $link_item);
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

/**
 * Returns the rendered HTML of the primary local tasks.
 */
function menu_primary_local_tasks() {
  $local_tasks = menu_get_local_tasks();
  $pid = menu_get_active_nontask_item();
  $output = '';

  if (count($local_tasks[$pid]['children'])) {
    foreach ($local_tasks[$pid]['children'] as $mid) {
      $output .= theme('menu_local_task', $mid, menu_in_active_trail($mid), TRUE);
    }
  }

  return $output;
}

/**
 * Returns the rendered HTML of the secondary local tasks.
 */
function menu_secondary_local_tasks() {
  $local_tasks = menu_get_local_tasks();
  $pid = menu_get_active_nontask_item();
  $output = '';

  if (count($local_tasks[$pid]['children'])) {
    foreach ($local_tasks[$pid]['children'] as $mid) {
      if (menu_in_active_trail($mid) && count($local_tasks[$mid]['children']) > 1) {
        foreach ($local_tasks[$mid]['children'] as $cid) {
          $output .= theme('menu_local_task', $cid, menu_in_active_trail($cid), FALSE);
        }
      }
    }
  }

  return $output;
}

/**
 * Generate the HTML representing a given menu item ID as a tab.
 *
 * @param $mid
 *   The menu ID to render.
 * @param $active
 *   Whether this tab or a subtab is the active menu item.
 * @param $primary
 *   Whether this tab is a primary tab or a subtab.
 *
 * @ingroup themeable
 */
function theme_menu_local_task($mid, $active, $primary) {
  if ($active) {
    return '<li class="active">'. menu_item_link($mid) ."</li>\n";
  }
  else {
    return '<li>'. menu_item_link($mid) ."</li>\n";
  }
}

/**
 * Returns an array containing the primary links.
 * Can optionally descend from the root of the Primary links menu towards the
 * current node for a specified number of levels and return that submenu.
 * Used to generate a primary/secondary menu from different levels of one menu.
 *
 * @param $start_level
 *   This optional parameter can be used to retrieve a context-sensitive array
 *   of links at $start_level levels deep into the Primary links menu.
 *   The default is to return the top-level links.
 * @param $pid
 *   The parent menu ID from which to search for children. Defaults to the
 *   menu_primary_menu setting.
 * @return An array containing the themed links as the values. The keys of
 *   the array contain some extra encoded information about the results.
 *   The format of the key is {level}-{num}{-active}.
 *   level is the depth within the menu tree of this list.
 *   num is the number within this array, used only to make the key unique.
 *   -active is appended if this element is in the active trail.
 */
function menu_primary_links($start_level = 1, $pid = 0) {
  if (!module_exist('menu')) {
    return NULL;
  }
  if (!$pid) {
    $pid = variable_get('menu_primary_menu', 0);
  }
  if (!$pid) {
    return NULL;
  }

  if ($start_level < 1) {
    $start_level = 1;
  }

  if ($start_level > 1) {
    $trail = _menu_get_active_trail_in_submenu($pid);
    if (!$trail) {
      return NULL;
    }
    else {
      $pid = $trail[$start_level - 1];
    }
  }

  $menu = menu_get_menu();
  $links = array();
  if ($pid && is_array($menu['visible'][$pid]) && isset($menu['visible'][$pid]['children'])) {
    $count = 1;
    foreach ($menu['visible'][$pid]['children'] as $cid) {
      $index = "menu-$start_level-$count";
      if (menu_in_active_trail_in_submenu($cid, $pid)) {
        $index .= "-active";
      }
      $links[$index] = menu_item_link($cid);
      $count++;
    }
  }

  // Special case - provide link to admin/menu if primary links is empty.
  if (empty($links) && $start_level == 1 && $pid == variable_get('menu_primary_menu', 0) && user_access('administer menu')) {
    $links['1-1'] = l(t('edit primary links'),'admin/menu');
  }

  return $links;
}

/**
 * Returns an array containing the secondary links.
 * Secondary links can be either a second level of the Primary links
 * menu or generated from their own menu.
 */
function menu_secondary_links() {
  $msm = variable_get('menu_secondary_menu', 0);
  if ($msm == 0) {
    return NULL;
  }

  if ($msm == variable_get('menu_primary_menu', 0)) {
    return menu_primary_links(2, $msm);
  }

  return menu_primary_links(1, $msm);
}

/**
 * Returns the themed HTML for primary and secondary links.
 * Note that this function is overridden by most core themes because
 * those themes display links in "link | link" format, not from a list.
 * Also note that by default links rendered with this function are
 * displayed with the same CSS as is used for the local tasks.
 * If a theme wishes to render links from a ul it is expected that
 * the theme will provide suitable CSS.
 *
 * @param $links
 *   An array containing links to render.
 * @return
 *   A string containing the themed links.
 *
 * @ingroup themeable
 */
function theme_menu_links($links) {
  if (!count($links)) {
    return '';
  }
  $level_tmp = explode('-', key($links));
  $level = $level_tmp[0];
  $output = "<ul class=\"links-$level\">\n";
  foreach ($links as $index => $link) {
    $output .= '<li';
    if (stristr($index, 'active')) {
      $output .= ' class="active"';
    }
    $output .= ">$link</li>\n";
  }
  $output .= '</ul>';

  return $output;
}

/**
 * @} End of "defgroup menu".
 */

/**
 * Returns an array with the menu items that lead to the current menu item.
 */
function _menu_get_active_trail() {
  static $trail;

  if (!isset($trail)) {
    $trail = array();

    $mid = menu_get_active_item();

    // Follow the parents up the chain to get the trail.
    while ($mid && ($item = menu_get_item($mid))) {
      array_unshift($trail, $mid);
      $mid = $item['pid'];
    }
  }
  return $trail;
}

/**
 * Find the active trail through a specific subsection of the menu tree.
 *
 * @param $pid
 *   The root item from which the active trail must descend.
 */
function _menu_get_active_trail_in_submenu($pid) {
  static $trails;

  if (!isset($trails)) {
    // Find all menu items which point to the current node and for each
    // follow the parents up the chain to build an active trail.
    $trails = array();
    $menu = menu_get_menu();
    $path = $_GET['q'];
    $count = 0;
    while ($path && !$count) {
      foreach ($menu['items'] as $key => $item) {
        if (isset($item['path']) && $item['path'] == $path) {
          $trails[$count] = array();
          $mid = $key;
          while ($mid && $menu['items'][$mid]) {
            array_unshift($trails[$count], $mid);
            $mid = $menu['items'][$mid]['pid'];
          }
          $count ++;
        }
      }
      $path = substr($path, 0, strrpos($path, '/'));
    }
  }

  if ($trails) {
    foreach ($trails as $trail) {
      $count_trail = count($trail);
      for ($i = 0; $i < $count_trail; $i++) {
        if ($trail[$i] == $pid) {
          // Return a trail from $pid down to the current page inclusive.
          for ( ; $i < $count_trail; $i++) {
            $subtrail[] = $trail[$i];
          }
          return $subtrail;
        }
      }
    }
  }

  return NULL;
}

/**
 * Comparator routine for use in sorting menu items.
 */
function _menu_sort($a, $b) {
  $menu = menu_get_menu();

  $a = &$menu['items'][$a];
  $b = &$menu['items'][$b];

  if ($a['weight'] < $b['weight']) {
    return -1;
  }
  elseif ($a['weight'] > $b['weight']) {
    return 1;
  }
  elseif (isset($a['title']) && isset($b['title'])) {
    return strnatcasecmp($a['title'], $b['title']);
  }
  else {
    return 1;
  }
}

/**
 * Build the menu by querying both modules and the database.
 */
function _menu_build() {
  global $_menu;
  global $user;

  // Start from a clean slate.
  $_menu = array();

  $_menu['path index'] = array();
  // Set up items array, including default "Navigation" menu.
  $_menu['items'] = array(
    0 => array('path' => '', 'title' => '', 'type' => MENU_IS_ROOT),
    1 => array('pid' => 0, 'path' => '', 'title' => t('Navigation'), 'weight' => -50, 'access' => TRUE, 'type' => MENU_IS_ROOT | MENU_VISIBLE_IN_TREE)
    );
  $_menu['callbacks'] = array();

  // Build a sequential list of all menu items.
  $menu_item_list = module_invoke_all('menu', TRUE);

  // Menu items not in the DB get temporary negative IDs.
  $temp_mid = -1;

  foreach ($menu_item_list as $item) {
    if (!isset($item['path'])) {
      $item['path'] = '';
    }
    if (!isset($item['type'])) {
      $item['type'] = MENU_NORMAL_ITEM;
    }
    if (!isset($item['weight'])) {
      $item['weight'] = 0;
    }
    $mid = $temp_mid;
    if (isset($_menu['path index'][$item['path']])) {
      // Newer menu items overwrite older ones.
      unset($_menu['items'][$_menu['path index'][$item['path']]]);
    }
    if (isset($item['callback'])) {
      $_menu['callbacks'][$item['path']] = array('callback' => $item['callback']);
      if (isset($item['callback arguments'])) {
        $_menu['callbacks'][$item['path']]['callback arguments'] = $item['callback arguments'];
      }
    }
    unset($item['callback']);
    unset($item['callback arguments']);
    $_menu['items'][$mid] = $item;
    $_menu['path index'][$item['path']] = $mid;

    $temp_mid--;
  }

  // Now fetch items from the DB, reassigning menu IDs as needed.
  if (module_exist('menu')) {
    $result = db_query(db_rewrite_sql('SELECT m.mid, m.* FROM {menu} m ORDER BY m.mid ASC', 'm', 'mid'));
    while ($item = db_fetch_object($result)) {
      // Handle URL aliases if entered in menu administration.
      if (!isset($_menu['path index'][$item->path])) {
        $item->path = drupal_get_normal_path($item->path);
      }
      if (isset($_menu['path index'][$item->path])) {
        // The path is already declared.
        $old_mid = $_menu['path index'][$item->path];
        if ($old_mid < 0) {
          // It had a temporary ID, so use a permanent one.
          $_menu['items'][$item->mid] = $_menu['items'][$old_mid];
          unset($_menu['items'][$old_mid]);
          $_menu['path index'][$item->path] = $item->mid;
        }
        else {
          // It has a permanent ID. Only replace with non-custom menu items.
          if ($item->type & MENU_CREATED_BY_ADMIN) {
            $_menu['items'][$item->mid] = array('path' => $item->path);
          }
          else {
            // Leave the old item around as a shortcut to this one.
            $_menu['items'][$item->mid] = $_menu['items'][$old_mid];
            $_menu['path index'][$item->path] = $item->mid;
          }
        }
      }
      else {
        // The path was not declared, so this is a custom item or an orphaned one.
        if ($item->type & MENU_CREATED_BY_ADMIN) {
          $_menu['items'][$item->mid] = array('path' => $item->path);
          if (!empty($item->path)) {
            $_menu['path index'][$item->path] = $item->mid;
          }
        }
      }

      // If the administrator has changed the item, reflect the change.
      if ($item->type & MENU_MODIFIED_BY_ADMIN) {
        $_menu['items'][$item->mid]['title'] = $item->title;
        $_menu['items'][$item->mid]['description'] = $item->description;
        $_menu['items'][$item->mid]['pid'] = $item->pid;
        $_menu['items'][$item->mid]['weight'] = $item->weight;
        $_menu['items'][$item->mid]['type'] = $item->type;
      }
    }
  }

  // Associate parent and child menu items.
  _menu_find_parents($_menu['items']);

  // Prepare to display trees to the user as required.
  _menu_build_visible_tree();
}

/**
 * Determine whether the given menu item is accessible to the current user.
 *
 * Use this instead of just checking the "access" property of a menu item
 * to properly handle items with fall-through semantics.
 */
function _menu_item_is_accessible($mid) {
  $menu = menu_get_menu();

  // Follow the path up to find the first "access" attribute.
  $path = isset($menu['items'][$mid]['path']) ? $menu['items'][$mid]['path'] : NULL;
  while ($path && (!isset($menu['path index'][$path]) || !isset($menu['items'][$menu['path index'][$path]]['access']))) {
    $path = substr($path, 0, strrpos($path, '/'));
  }
  if (empty($path)) {
    // Items without any access attribute up the chain are denied, unless they
    // were created by the admin. They most likely point to non-Drupal directories
    // or to an external URL and should be allowed.
    return $menu['items'][$mid]['type'] & MENU_CREATED_BY_ADMIN;
  }
  return $menu['items'][$menu['path index'][$path]]['access'];
}

/**
 * Find all visible items in the menu tree, for ease in displaying to user.
 *
 * Since this is only for display, we only need title, path, and children
 * for each item.
 */
function _menu_build_visible_tree($pid = 0) {
  global $_menu;

  if (isset($_menu['items'][$pid])) {
    $parent = $_menu['items'][$pid];

    $children = array();
    if (isset($parent['children'])) {
      usort($parent['children'], '_menu_sort');
      foreach ($parent['children'] as $mid) {
        $children = array_merge($children, _menu_build_visible_tree($mid));
      }
    }
    $visible = ($parent['type'] & MENU_VISIBLE_IN_TREE) ||
      ($parent['type'] & MENU_VISIBLE_IF_HAS_CHILDREN && count($children) > 0);
    $allowed = _menu_item_is_accessible($pid);

    if (($parent['type'] & MENU_IS_ROOT) || ($visible && $allowed)) {
      $_menu['visible'][$pid] = array('title' => $parent['title'], 'path' => $parent['path'], 'children' => $children, 'type' => $parent['type']);
      foreach ($children as $mid) {
        $_menu['visible'][$mid]['pid'] = $pid;
      }
      return array($pid);
    }
    else {
      return $children;
    }
  }

  return array();
}

/**
 * Account for menu items that are only defined at certain paths, so will not
 * be cached.
 *
 * We don't support the full range of menu item options for these menu items. We
 * don't support MENU_VISIBLE_IF_HAS_CHILDREN, and we require parent items to be
 * declared before their children.
 */
function _menu_append_contextual_items() {
  global $_menu;

  // Build a sequential list of all menu items.
  $menu_item_list = module_invoke_all('menu', FALSE);

  // Menu items not in the DB get temporary negative IDs.
  $temp_mid = min(array_keys($_menu['items'])) - 1;
  $new_items = array();

  foreach ($menu_item_list as $item) {
    if (isset($item['callback'])) {
      $_menu['callbacks'][$item['path']] = array('callback' => $item['callback']);
      if (isset($item['callback arguments'])) {
        $_menu['callbacks'][$item['path']]['callback arguments'] = $item['callback arguments'];
      }
    }
    unset($item['callback']);
    unset($item['callback arguments']);
    if (!isset($_menu['path index'][$item['path']])) {
      if (!isset($item['path'])) {
        $item['path'] = '';
      }
      if (!isset($item['type'])) {
        $item['type'] = MENU_NORMAL_ITEM;
      }
      if (!isset($item['weight'])) {
        $item['weight'] = 0;
      }
      $_menu['items'][$temp_mid] = $item;
      $_menu['path index'][$item['path']] = $temp_mid;
      $new_items[$temp_mid] = $item;
      $temp_mid--;
    }
    else {
      $mid = $_menu['path index'][$item['path']];
      if ($_menu['items'][$mid]['type'] & MENU_CREATED_BY_ADMIN) {
        $_menu['items'][$mid]['access'] = $item['access'];
        if (isset($_menu['items'][$mid]['callback'])) {
          $_menu['items'][$mid]['callback'] = $item['callback'];
        }
        if (isset($_menu['items'][$mid]['callback arguments'])) {
          $_menu['items'][$mid]['callback arguments'] = $item['callback arguments'];
        }
      }
    }
  }

  // Establish parent-child relationships.
  _menu_find_parents($new_items);

  // Add new items to the visible tree if necessary.
  foreach ($new_items as $mid => $item) {
    $item = $_menu['items'][$mid];
    if (($item['type'] & MENU_VISIBLE_IN_TREE) && _menu_item_is_accessible($mid)) {
      $pid = $item['pid'];
      while ($pid && !isset($_menu['visible'][$pid])) {
        $pid = $_menu['items'][$pid]['pid'];
      }
      $_menu['visible'][$mid] = array('title' => $item['title'], 'path' => $item['path'], 'pid' => $pid);
      $_menu['visible'][$pid]['children'][] = $mid;
      usort($_menu['visible'][$pid]['children'], '_menu_sort');
    }
  }
}

/**
 * Establish parent-child relationships.
 */
function _menu_find_parents(&$items) {
  global $_menu;

  foreach ($items as $mid => $item) {
    if (!isset($item['pid'])) {
      // Parent's location has not been customized, so figure it out using the path.
      $parent = $item['path'];
      if ($parent) {
        do {
          $parent = substr($parent, 0, strrpos($parent, '/'));
        }
        while ($parent && !isset($_menu['path index'][$parent]));
      }

      $pid = $parent ? $_menu['path index'][$parent] : 1;
      $_menu['items'][$mid]['pid'] = $pid;
    }
    else {
      $pid = $item['pid'];
    }

    // Don't make root a child of itself.
    if ($mid) {
      if (isset ($_menu['items'][$pid])) {
        $_menu['items'][$pid]['children'][] = $mid;
      }
      else {
        // If parent is missing, it is a menu item that used to be defined
        // but is no longer. Default to a root-level "Navigation" menu item.
        $_menu['items'][1]['children'][] = $mid;
      }
    }
  }
}

/**
 * Find all the items in the current local task tree.
 *
 * Since this is only for display, we only need title, path, and children
 * for each item.
 *
 * At the close of this function, $_menu['local tasks'] is populated with the
 * menu items in the local task tree.
 *
 * @return
 *   TRUE if the local task tree is forked. It does not need to be displayed
 *   otherwise.
 */
function _menu_build_local_tasks($pid) {
  global $_menu;

  $forked = FALSE;

  if (isset($_menu['items'][$pid])) {
    $parent = $_menu['items'][$pid];

    $children = array();
    if (isset($parent['children'])) {
      foreach ($parent['children'] as $mid) {
        if (($_menu['items'][$mid]['type'] & MENU_IS_LOCAL_TASK) && _menu_item_is_accessible($mid)) {
          $children[] = $mid;
          // Beware short-circuiting || operator!
          $forked = _menu_build_local_tasks($mid) || $forked;
        }
      }
    }
    usort($children, '_menu_sort');
    $forked = $forked || count($children) > 1;

    $_menu['local tasks'][$pid] = array('title' => $parent['title'], 'path' => $parent['path'], 'children' => $children);
    foreach ($children as $mid) {
      $_menu['local tasks'][$mid]['pid'] = $pid;
    }
  }

  return $forked;
}

/**
 * Returns TRUE if the site is off-line for maintenance.
 */
function _menu_site_is_offline() {
  // Check if site is set to off-line mode
  if (variable_get('site_offline', 0)) {
    // Check if the user has administration privileges
    if (!user_access('administer site configuration')) {
      // Check if this is an attempt to login
      if (drupal_get_normal_path($_GET['q']) != 'user') {
        return TRUE;
      }
    }
    else {
      $offline_message = t('Operating in off-line mode.');
      $messages = drupal_set_message();
      // Ensure that the off-line message is displayed only once [allowing for page redirects].
      if (!isset($messages) || !isset($messages['status']) || !in_array($offline_message, $messages['status'])) {
        drupal_set_message($offline_message);
      }
    }
  }
  return FALSE;
}