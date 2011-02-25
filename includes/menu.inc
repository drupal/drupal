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
 * The found callback function is called with any arguments specified in
 * the "callback arguments" attribute of its menu item. After these
 * arguments, any remaining components of the path are appended as further
 * arguments. In this way, the callback for a/b above could respond to a
 * request for a/b/i differently than a request for a/b/j.
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
define('MENU_SUGGESTED_ITEM', MENU_MODIFIABLE_BY_ADMIN);

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

  if (!isset($_menu['items'])) {
    // _menu_build() may indirectly call this function, so prevent infinite loops.
    $_menu['items'] = array();

    $cid = 'menu:'. $user->uid;
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

  foreach (array_reverse($location) as $item) {
    if (isset($_menu['path index'][$item['path']])) {
      $mid = $_menu['path index'][$item['path']];
      if (isset ($_menu['visible'][$mid])) {
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
  $menu = menu_get_menu();

  // Determine the menu item containing the callback.
  $path = $_GET['q'];
  while ($path && (!array_key_exists($path, $menu['path index']) || empty($menu['items'][$menu['path index'][$path]]['callback']))) {
    $path = substr($path, 0, strrpos($path, '/'));
  }
  if (!array_key_exists($path, $menu['path index'])) {
    return MENU_NOT_FOUND;
  }
  $mid = $menu['path index'][$path];

  if (empty($menu['items'][$mid]['callback'])) {
    return MENU_NOT_FOUND;
  }

  if (!_menu_item_is_accessible(menu_get_active_item())) {
    return MENU_ACCESS_DENIED;
  }

  // We found one, and are allowed to execute it.
  $arguments = array_key_exists('callback arguments', $menu['items'][$mid]) ? $menu['items'][$mid]['callback arguments'] : array();
  $arg = substr($_GET['q'], strlen($menu['items'][$mid]['path']) + 1);
  if (strlen($arg)) {
    $arguments = array_merge($arguments, explode('/', $arg));
  }
  call_user_func_array($menu['items'][$mid]['callback'], $arguments);
  return MENU_FOUND;
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
  $menu = menu_get_menu();

  if (is_null($stored_mid) || !empty($path)) {
    if (empty($path)) {
      $path = $_GET['q'];
    }
    else {
      $_GET['q'] = $path;
    }

    while ($path && !array_key_exists($path, $menu['path index'])) {
      $path = substr($path, 0, strrpos($path, '/'));
    }
    $stored_mid = array_key_exists($path, $menu['path index']) ? $menu['path index'][$path] : 0;

    // Search for default local tasks to activate instead of this item.
    $continue = TRUE;
    while ($continue) {
      $continue = FALSE;
      if (array_key_exists('children', $menu['items'][$stored_mid])) {
        foreach ($menu['items'][$stored_mid]['children'] as $cid) {
          if ($menu['items'][$cid]['type'] & MENU_LINKS_TO_PARENT) {
            $stored_mid = $cid;
            $continue = TRUE;
          }
        }
      }
    }
  }

  return $stored_mid;
}

/**
 * Returns the ID of the current menu item or, if the current item is a
 * local task, the menu item to which this task is attached.
 */
function menu_get_active_nontask_item() {
  $menu = menu_get_menu();
  $mid = menu_get_active_item();

  // Find the first non-task item:
  while ($mid && ($menu['items'][$mid]['type'] & MENU_IS_LOCAL_TASK)) {
    $mid = $menu['items'][$mid]['pid'];
  }

  if ($mid) {
    return $mid;
  }
}

/**
 * Returns the title of the active menu item.
 */
function menu_get_active_title() {
  $menu = menu_get_menu();

  if ($mid = menu_get_active_nontask_item()) {
    return $menu['items'][$mid]['title'];
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
  $menu = menu_get_menu();

  $links[] = l(t('Home'), '');

  $trail = _menu_get_active_trail();
  foreach ($trail as $mid) {
    if ($menu['items'][$mid]['type'] & MENU_VISIBLE_IN_BREADCRUMB) {
      $links[] = theme('menu_item', $mid);
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

    $new_items = array();
    foreach ($menu['items'] as $mid => $item) {
      if ($mid < 0 && ($item['type'] & MENU_MODIFIABLE_BY_ADMIN)) {
        $new_mid = db_next_id('{menu}_mid');
        // Check explicitly for mid 1. If the database was improperly prefixed,
        // this would cause a nasty infinite loop.
        // TODO: have automatic prefixing through an installer to prevent this.
        if ($new_mid == 1) {
          $new_mid = db_next_id('{menu}_mid');
        }
        if (isset($new_items[$item['pid']])) {
          $new_pid = $new_items[$item['pid']]['mid'];
        }
        else {
          $new_pid = $item['pid'];
        }

        // Fix parent IDs for menu items already added.
        if ($item['children']) {
          foreach ($item['children'] as $child) {
            if (isset($new_items[$child])) {
              $new_items[$child]['pid'] = $new_mid;
            }
          }
        }

        $new_items[$mid] = array('mid' => $new_mid, 'pid' => $new_pid, 'path' => $item['path'], 'title' => $item['title'], 'description' => array_key_exists('description', $item) ? $item['description'] : '', 'weight' => $item['weight'], 'type' => $item['type']);
      }
    }

    if (count($new_items) > 0) {
      foreach ($new_items as $item) {
        db_query('INSERT INTO {menu} (mid, pid, path, title, description, weight, type) VALUES (%d, %d, \'%s\', \'%s\', \'%s\', %d, %d)', $item['mid'], $item['pid'], $item['path'], $item['title'], $item['description'], $item['weight'], $item['type']);
      }

      // Rebuild the menu to account for the changes.
      _menu_build();
    }
  }
}

/**
 * Returns a rendered menu tree.
 *
 * @ingroup themeable
 */
function theme_menu_tree($pid = 1, $all = FALSE) {
  $menu = menu_get_menu();
  $output = '';

  if (isset($menu['visible'][$pid]) && $menu['visible'][$pid]['children']) {

    foreach ($menu['visible'][$pid]['children'] as $mid) {
      $style = (count($menu['visible'][$mid]['children']) ? (menu_in_active_trail($mid)  ? 'expanded' : 'collapsed') : 'leaf');
      $output .= "<li class=\"$style\">";
      $output .= theme('menu_item', $mid);
      if ($all || menu_in_active_trail($mid)) {
        $output .= theme('menu_tree', $mid);
      }
      $output .= "</li>\n";
    }

    if ($output != '') {
      $output  = "\n<ul>\n$output\n</ul>\n";
    }
  }

  return $output;
}

/**
 * Generate the HTML representing a given menu item ID.
 *
 * @param $mid
 *   The menu ID to render.
 *
 * @ingroup themeable
 */
function theme_menu_item($mid) {
  $menu = menu_get_menu();

  $link_mid = $mid;
  while ($menu['items'][$link_mid]['type'] & MENU_LINKS_TO_PARENT) {
    $link_mid = $menu['items'][$link_mid]['pid'];
  }

  return l($menu['items'][$mid]['title'], $menu['items'][$link_mid]['path'], array_key_exists('description', $menu['items'][$mid]) ? array("title" => $menu['items'][$mid]['description']) : array());
}

/**
 * Returns the rendered local tasks. The default implementation renders
 * them as tabs.
 *
 * @ingroup themeable
 */
function theme_menu_local_tasks() {
  $local_tasks = menu_get_local_tasks();
  $pid = menu_get_active_nontask_item();
  $output = '';

  if (count($local_tasks[$pid]['children'])) {
    $output .= "<ul class=\"tabs primary\">\n";
    foreach ($local_tasks[$pid]['children'] as $mid) {
      $output .= theme('menu_local_task', $mid, menu_in_active_trail($mid));
    }
    $output .= "</ul>\n";

    foreach ($local_tasks[$pid]['children'] as $mid) {
      if (menu_in_active_trail($mid) && count($local_tasks[$mid]['children'])) {
        $output .= "<ul class=\"tabs secondary\">\n";
        foreach ($local_tasks[$mid]['children'] as $cid) {
          $output .= theme('menu_local_task', $cid, menu_in_active_trail($cid));
        }
        $output .= "</ul>\n";
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
 *
 * @ingroup themeable
 */
function theme_menu_local_task($mid, $active) {
  if ($active) {
    return '<li class="active">'. theme('menu_item', $mid) ."</li>\n";
  }
  else {
    return '<li>'. theme('menu_item', $mid) ."</li>\n";
  }
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
    $menu = menu_get_menu();

    $trail = array();

    $mid = menu_get_active_item();

    // Follow the parents up the chain to get the trail.
    while ($mid && $menu['items'][$mid]) {
      array_unshift($trail, $mid);
      $mid = $menu['items'][$mid]['pid'];
    }
  }
  return $trail;
}

/**
 * Comparator routine for use in sorting menu items.
 */
function _menu_sort($a, $b) {
  $menu = menu_get_menu();

  $a = &$menu['items'][$a];
  $b = &$menu['items'][$b];

  return $a['weight'] < $b['weight'] ? -1 : ($a['weight'] > $b['weight'] ? 1 : ($a['title'] < $b['title'] ? -1 : 1));
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

  // Build a sequential list of all menu items.
  $menu_item_list = module_invoke_all('menu', TRUE);

  // Menu items not in the DB get temporary negative IDs.
  $temp_mid = -1;

  foreach ($menu_item_list as $item) {
    if (!array_key_exists('path', $item)) {
      $item['path'] = '';
    }
    if (!array_key_exists('type', $item)) {
      $item['type'] = MENU_NORMAL_ITEM;
    }
    if (!array_key_exists('weight', $item)) {
      $item['weight'] = 0;
    }
    $mid = $temp_mid;
    if (array_key_exists($item['path'], $_menu['path index'])) {
      // Newer menu items overwrite older ones.
      unset($_menu['items'][$_menu['path index'][$item['path']]]);
    }
    $_menu['items'][$mid] = $item;
    $_menu['path index'][$item['path']] = $mid;

    $temp_mid--;
  }

  // Now fetch items from the DB, reassigning menu IDs as needed.
  if (module_exist('menu')) {
    $result = db_query('SELECT * FROM {menu} ORDER BY mid ASC');
    while ($item = db_fetch_object($result)) {
      if (array_key_exists($item->path, $_menu['path index'])) {
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
            $_menu['items'][$item->mid] = array('path' => $item->path, 'callback' => '');
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
          $_menu['items'][$item->mid] = array('path' => $item->path, 'callback' => '');
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
  $path = $menu['items'][$mid]['path'];
  while ($path && (!array_key_exists($path, $menu['path index']) || !array_key_exists('access', $menu['items'][$menu['path index'][$path]]))) {
    $path = substr($path, 0, strrpos($path, '/'));
  }
  if (empty($path)) {
    return FALSE;
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
    if (array_key_exists('children', $parent)) {
      usort($parent['children'], '_menu_sort');
      foreach ($parent['children'] as $mid) {
        $children = array_merge($children, _menu_build_visible_tree($mid));
      }
    }
    $visible = ($parent['type'] & MENU_VISIBLE_IN_TREE) ||
      ($parent['type'] & MENU_VISIBLE_IF_HAS_CHILDREN && count($children) > 0);
    $allowed = _menu_item_is_accessible($pid);

    if (($parent['type'] & MENU_IS_ROOT) || ($visible && $allowed)) {
      $_menu['visible'][$pid] = array('title' => $parent['title'], 'path' => $parent['path'], 'children' => $children);
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
    if (array_key_exists($item['path'], $_menu['path index'])) {
      // The menu item already exists, so just add appropriate callback information.
      $mid = $_menu['path index'][$item['path']];

      $_menu['items'][$mid]['access'] = $item['access'];
      $_menu['items'][$mid]['callback'] = $item['callback'];
      $_menu['items'][$mid]['callback arguments'] = $item['callback arguments'];
    }
    else {
      if (!array_key_exists('path', $item)) {
        $item['path'] = '';
      }
      if (!array_key_exists('type', $item)) {
        $item['type'] = MENU_NORMAL_ITEM;
      }
      if (!array_key_exists('weight', $item)) {
        $item['weight'] = 0;
      }
      $_menu['items'][$temp_mid] = $item;
      $_menu['path index'][$item['path']] = $temp_mid;
      $new_items[$temp_mid] = $item;
      $temp_mid--;
    }
  }

  // Establish parent-child relationships.
  _menu_find_parents($new_items);

  // Add new items to the visible tree if necessary.
  foreach ($new_items as $mid => $item) {
    $item = $_menu['items'][$mid];
    if (($item['type'] & MENU_VISIBLE_IN_TREE) && _menu_item_is_accessible($mid)) {
      $pid = $item['pid'];
      while ($pid && !array_key_exists($pid, $_menu['visible'])) {
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
        while ($parent && !array_key_exists($parent, $_menu['path index']));
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
    if (array_key_exists('children', $parent)) {
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

?>
