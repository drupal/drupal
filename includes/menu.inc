<?php
/* $Id$ */

/**
 * @defgroup menu Menu system
 * @{
 */

/**
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
define('MENU_IS_LOCAL_SUBTASK', 0x0100);

/**
 * Normal menu items show up in the menu tree and can be moved/hidden by
 * the administrator.
 */
define('MENU_NORMAL_ITEM', MENU_VISIBLE_IN_TREE | MENU_VISIBLE_IN_BREADCRUMB | MENU_MODIFIABLE_BY_ADMIN);

/**
 * Item groupings are used for pages like "node/add" that simply list
 * subpages to visit.
 */
define('MENU_ITEM_GROUPING', MENU_VISIBLE_IF_HAS_CHILDREN | MENU_VISIBLE_IN_BREADCRUMB | MENU_MODIFIABLE_BY_ADMIN);

/**
 * Callbacks simply register a path so that the correct function is fired
 * when the URL is accessed.
 */
define('MENU_CALLBACK', MENU_VISIBLE_IN_BREADCRUMB);

/**
 * Dynamic menu items change frequently, and so should not be stored in the
 * database for administrative customization.
 */
define('MENU_DYNAMIC_ITEM', MENU_VISIBLE_IN_TREE | MENU_VISIBLE_IN_BREADCRUMB);

/**
 * Modules may "suggest" menu items that the administrator may enable.
 */
define('MENU_SUGGESTED_ITEM', MENU_MODIFIABLE_BY_ADMIN);

/**
 * Local tasks are rendered as tabs by default.
 */
define('MENU_LOCAL_TASK', MENU_IS_LOCAL_TASK);

/**
 * Local subtasks are rendered as a horizontal listing below the tabs by default.
 */
define('MENU_LOCAL_SUBTASK', MENU_IS_LOCAL_SUBTASK);

/**
 * Custom items are those defined by the administrator.
 */
define('MENU_CUSTOM_ITEM', MENU_VISIBLE_IN_TREE | MENU_VISIBLE_IN_BREADCRUMB | MENU_CREATED_BY_ADMIN | MENU_MODIFIABLE_BY_ADMIN);

/**
 * Custom menus are those defined by the administrator.
 */
define('MENU_CUSTOM_MENU', MENU_IS_ROOT | MENU_VISIBLE_IN_TREE | MENU_CREATED_BY_ADMIN | MENU_MODIFIABLE_BY_ADMIN);

/**
 * Status codes for menu callbacks.
 */
define('MENU_FOUND', 1);
define('MENU_NOT_FOUND', 2);
define('MENU_ACCESS_DENIED', 3);

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
 * - 'path' - The Drupal path to the menu item. A link to a particular item
 *   can thus be constructed with l($item['title'], $item['path']).
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
    _menu_build();
  }

  // Don't cache the local task tree, as it varies by location and tasks are
  // allowed to be dynamically determined.
  if (!isset($_menu['local tasks'])) {
    // _menu_build_local_tasks() may indirectly call this function, so prevent
    // infinite loops.
    $_menu['local tasks'] = array();
    _menu_build_local_tasks();
  }

  return $_menu;
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
  while ($path && (!$menu['path index'][$path] || !$menu['items'][$menu['path index'][$path]]['callback'])) {
    $path = substr($path, 0, strrpos($path, '/'));
  }
  $mid = $menu['path index'][$path];

  if (!is_string($menu['items'][$mid]['callback'])) {
    return MENU_NOT_FOUND;
  }

  if (!_menu_item_is_accessible(menu_get_active_item())) {
    return MENU_ACCESS_DENIED;
  }

  // We found one, and are allowed to execute it.
  $arguments = $menu['items'][$mid]['callback arguments'] ? $menu['items'][$mid]['callback arguments'] : array();
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

    while ($path && !$menu['path index'][$path]) {
      $path = substr($path, 0, strrpos($path, '/'));
    }
    $stored_mid = $menu['path index'][$path];
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
  while ($mid && (($menu['items'][$mid]['type'] & MENU_LOCAL_TASK) || ($menu['items'][$mid]['type'] & MENU_LOCAL_SUBTASK))) {
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
    return ucfirst($menu['items'][$mid]['title']);
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

  $return = module_invoke_all('help', $path);
  foreach ($return as $item) {
    if (!empty($item)) {
      $output .= $item ."\n";
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

  $trail = _menu_get_trail($_GET['q']);
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
  static $trail;

  if (empty($trail)) {
    $trail = _menu_get_trail($_GET['q']);
  }

  return in_array($mid, $trail);
}

/**
 * Populate the database representation of the menu.
 *
 * This need only be called at the start of pages that modify the menu.
 */
function menu_rebuild() {
  cache_clear_all();
  _menu_build();
  $menu = menu_get_menu();

  $new_items = array();
  foreach ($menu['items'] as $mid => $item) {
    if ($mid < 0 && ($item['type'] & MENU_MODIFIABLE_BY_ADMIN)) {
      $new_mid = db_next_id('menu_mid');
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

      $new_items[$mid] = array('mid' => $new_mid, 'pid' => $new_pid, 'path' => $item['path'], 'title' => $item['title'], 'weight' => $item['weight'], 'type' => $item['type']);
    }
  }

  foreach ($new_items as $item) {
    db_query('INSERT INTO {menu} (mid, pid, path, title, weight, type) VALUES (%d, %d, \'%s\', \'%s\', %d, %d)', $item['mid'], $item['pid'], $item['path'], $item['title'], $item['weight'], $item['type']);
  }

  // Rebuild the menu to account for any changes.
  _menu_build();
}

/** @} end of "menu" function group */

/**
 * @addtogroup themeable
 * @{
 */

/**
 * Returns a rendered menu tree.
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
 */
function theme_menu_item($mid) {
  $menu = menu_get_menu();

  return l($menu['items'][$mid]['title'], $menu['items'][$mid]['path']);
}

/**
 * Returns the rendered local tasks. The default implementation renders
 * them as tabs.
 */
function theme_menu_local_tasks() {
  $menu = menu_get_menu();
  $output = '';

  if (count($menu['local tasks'][0]['children'])) {
    $output .= "<ul class=\"tabs primary\">\n";
    foreach ($menu['local tasks'][0]['children'] as $mid) {
      $output .= theme('menu_local_task', $mid, menu_in_active_trail($mid));
    }
    $output .= "</ul>\n";

    foreach ($menu['local tasks'][0]['children'] as $mid) {
      if (menu_in_active_trail($mid) && count($menu['local tasks'][$mid]['children'])) {
        $output .= "<ul class=\"tabs secondary\">\n";
        foreach ($menu['local tasks'][$mid]['children'] as $cid) {
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
 */
function theme_menu_local_task($mid, $active) {
  if ($active) {
    return '<li class="active">'. theme('menu_item', $mid) ."</li>\n";
  }
  else {
    return '<li>'. theme('menu_item', $mid) ."</li>\n";
  }
}

/** @} End of addtogroup themeable */

/**
 * Returns an array with the menu items that lead to the specified path.
 */
function _menu_get_trail($path) {
  $menu = menu_get_menu();

  $trail = array();

  // Find the ID of the given path.
  while ($path && !$menu['path index'][$path]) {
    $path = substr($path, 0, strrpos($path, '/'));
  }
  $mid = $menu['path index'][$path];

  // Follow the parents up the chain to get the trail.
  while ($mid && $menu['items'][$mid]) {
    array_unshift($trail, $mid);
    $mid = $menu['items'][$mid]['pid'];
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
    0 => array('type' => MENU_IS_ROOT),
    1 => array('pid' => 0, 'title' => t('Navigation'), 'weight' => -50, 'access' => TRUE, 'type' => MENU_IS_ROOT | MENU_VISIBLE_IN_TREE)
    );

  // Build a sequential list of all menu items.
  $menu_item_list = module_invoke_all('menu');

  // Menu items not in the DB get temporary negative IDs.
  $temp_mid = -1;

  foreach ($menu_item_list as $item) {
    if (!isset($item['type'])) {
      $item['type'] = MENU_NORMAL_ITEM;
    }
    $mid = $temp_mid;
    if (isset($_menu['path index'][$item['path']])) {
      // Newer menu items overwrite older ones.
      unset($_menu['items'][$_menu['path index'][$item['path']]]);
    }
    $_menu['items'][$mid] = $item;
    $_menu['path index'][$item['path']] = $mid;

    $temp_mid--;
  }

  // Now fetch items from the DB, reassigning menu IDs as needed.
  if (module_exist('menu')) {
    $result = db_query('SELECT * FROM {menu}');
    while ($item = db_fetch_object($result)) {
      // Don't display non-custom menu items if no module declared them.
      if ($old_mid = $_menu['path index'][$item->path]) {
        $_menu['items'][$item->mid] = $_menu['items'][$old_mid];
        unset($_menu['items'][$old_mid]);
        $_menu['path index'][$item->path] = $item->mid;
        // If administrator has changed item position, reflect the change.
        if ($item->type & MENU_MODIFIED_BY_ADMIN) {
          $_menu['items'][$item->mid]['title'] = $item->title;
          $_menu['items'][$item->mid]['pid'] = $item->pid;
          $_menu['items'][$item->mid]['weight'] = $item->weight;
          $_menu['items'][$item->mid]['type'] = $item->type;
        }
      }
      // Next, add any custom items added by the administrator.
      else if ($item->type & MENU_CREATED_BY_ADMIN) {
        $_menu['items'][$item->mid] = array('pid' => $item->pid, 'path' => $item->path, 'title' => $item->title, 'access' => TRUE, 'weight' => $item->weight, 'type' => $item->type);
        $_menu['path index'][$item->path] = $item->mid;
      }
    }
  }

  // Establish parent-child relationships.
  foreach ($_menu['items'] as $mid => $item) {
    if (!isset($item['pid'])) {
      // Parent's location has not been customized, so figure it out using the path.
      $parent = $item['path'];
      do {
        $parent = substr($parent, 0, strrpos($parent, '/'));
      }
      while ($parent && !$_menu['path index'][$parent]);

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

  if (isset($menu['items'][$mid]['access'])) {
    return $menu['items'][$mid]['access'];
  }

  // Follow the path up to find the actual callback.
  $path = $menu['items'][$mid]['path'];
  while ($path && (!$menu['path index'][$path] || !$menu['items'][$menu['path index'][$path]]['callback'])) {
    $path = substr($path, 0, strrpos($path, '/'));
  }
  $callback_mid = $menu['path index'][$path];
  return $menu['items'][$callback_mid]['access'];
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
    if ($parent['children']) {
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
 * Find all the items in the current local task tree.
 *
 * Since this is only for display, we only need title, path, and children
 * for each item.
 */
function _menu_build_local_tasks() {
  global $_menu;

  $tasks = array();
  $tasks[0] = array('children' => array());

  $mid = menu_get_active_nontask_item();
  $tasks[$mid] = array('title' => $_menu['items'][$mid]['title'], 'path' => $_menu['items'][$mid]['path'], 'children' => array());
  $tasks[0]['children'][] = $mid;

  // Find top-level tasks
  if ($children = $_menu['items'][$mid]['children']) {
    foreach ($children as $cid) {
      if (($_menu['items'][$cid]['type'] & MENU_IS_LOCAL_TASK) && _menu_item_is_accessible($cid)) {
        $tasks[$cid] = array('title' => $_menu['items'][$cid]['title'], 'path' => $_menu['items'][$cid]['path'], 'children' => array());
        $tasks[0]['children'][] = $cid;
      }
    }
  }
  usort($tasks[0]['children'], '_menu_sort');

  // Find subtasks
  foreach ($tasks[0]['children'] as $mid) {
    if ($children = $_menu['items'][$mid]['children']) {
      foreach ($children as $cid) {
        if (($_menu['items'][$cid]['type'] & MENU_IS_LOCAL_SUBTASK) && _menu_item_is_accessible($cid)) {
          $tasks[$cid] = array('title' => $_menu['items'][$cid]['title'], 'path' => $_menu['items'][$cid]['path'], 'children' => array());
          $tasks[$mid]['children'][] = $cid;
        }
      }
    }
    usort($tasks[$mid]['children'], '_menu_sort');
  }

  if (count($tasks) > 2) {
    $_menu['local tasks'] = $tasks;
  }
  else {
    $_menu['local tasks'] = array();
    $_menu['local tasks'][0] = array('children' => array());
  }
}

?>
