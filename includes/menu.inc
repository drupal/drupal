<?php
/* $Id$ */

/**
 * @defgroup menu Menu system
 * @{
 */

define('MENU_SHOW', 0);
define('MENU_HIDE', 1);
define('MENU_HIDE_NOCHILD', 2);

define('MENU_NORMAL', 0);
define('MENU_MODIFIED', 1);
define('MENU_LOCKED', 2);
define('MENU_CUSTOM', 3);

/** @} */

/**
 * Register a menu item with the menu system.
 *
 * @ingroup menu
 * @param $path Location then menu item refers to.
 * @param $title The title of the menu item to show in the rendered menu.
 * @param $callback The function to call when this is the active menu item.
 * @param $weight Heavier menu items sink down the menu.
 * @param $visibility
 * - MENU_SHOW - Show the menu item (default).
 * - MENU_HIDE - Hide the menu item, but register a callback.
 * - MENU_HIDE_NOCHILD - Hide the menu item when it has no children.
 * @param $status
 * - MENU_NORMAL - The menu item can be moved (default).
 * - MENU_MODIFIED - The administrator has moved or otherwise changed the menu item.
 * - MENU_LOCKED - The administrator may not modify the item.
 * - MENU_CUSTOM - The menu item was created by the administrator.
 */
function menu($path, $title, $callback = NULL, $weight = 0, $visibility = MENU_SHOW, $status = MENU_NORMAL) {
  global $_menu;

  // add the menu to the flat list of menu items:
  $_menu['list'][$path] = array('title' => $title, 'callback' => $callback, 'weight' => $weight, 'visibility' => $visibility, 'status' => $status);
}

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
    $cache = cache_get('menu:'. $user->uid);
    if ($cache) {
      $_menu = unserialize($cache->data);
    }
    else {
      menu_build();
      cache_set('menu:'. $user->uid, serialize($_menu), 1);
    }
  }
  return $_menu;
}

/**
 * Returns an array with the menu items that lead to the specified path.
 */
function menu_get_trail($path) {
  $menu = menu_get_menu();

  $trail = array();

  $mid = menu_get_active_item();
  while ($mid && $menu['items'][$mid]) {
    array_unshift($trail, $mid);
    $mid = $menu['items'][$mid]['pid'];
  }

  return $trail;
}

/**
 * Returns the ID of the active menu item.
 * @ingroup menu
 */
function menu_get_active_item() {
  return menu_set_active_item();
}

/**
 * Sets the path of the active menu item.
 * @ingroup menu
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
 * Returns the title of the active menu item.
 */
function menu_get_active_title() {
  $menu = menu_get_menu();

  if ($mid = menu_get_active_item()) {
    return ucfirst($menu['items'][$mid]['title']);
  }
}

/**
 * Returns the help associated with the active menu item.
 */
function menu_get_active_help() {

  if (menu_active_handler_exists()) {
    $path = $_GET['q'];
    $output = '';

    $return = module_invoke_all('help', $path);
    foreach ($return as $item) {
      if (!empty($item)) {
        $output .= $item ."\n";
      }
    }
    return $output;
  }
}

/**
 * Returns an array of rendered menu items in the active breadcrumb trail.
 */
function menu_get_active_breadcrumb() {
  $menu = menu_get_menu();

  $links[] = l(t('Home'), '');

  $trail = menu_get_trail($_GET['q']);
  foreach ($trail as $mid) {
    // Don't show menu items without valid link targets.
    if ($menu['items'][$mid]['path'] != '') {
      $links[] = _menu_render_item($mid);
    }
  }

  return $links;
}

/**
 * Execute the handler associated with the active menu item.
 */
function menu_execute_active_handler() {
  $menu = menu_get_menu();

  $path = $_GET['q'];
  while ($path && (!$menu['path index'][$path] || !$menu['items'][$menu['path index'][$path]]['callback'])) {
    $path = substr($path, 0, strrpos($path, '/'));
  }
  $mid = $menu['path index'][$path];

  if ($menu['items'][$mid]['callback']) {
    $arg = substr($_GET['q'], strlen($menu['items'][$mid]['path']) + 1);
    if (isset($arg)) {
      return call_user_func_array($menu['items'][$mid]['callback'], explode('/', $arg));
    }
    else {
      return call_user_func($menu['items'][$mid]['callback']);
    }
  }
}

/**
 * Return true if a valid callback can be called from the current path.
 */
function menu_active_handler_exists() {
  $menu = menu_get_menu();

  $path = $_GET['q'];
  while ($path && (!$menu['path index'][$path] || !$menu['items'][$menu['path index'][$path]]['callback'])) {
    $path = substr($path, 0, strrpos($path, '/'));
  }
  $mid = $menu['path index'][$path];

  return function_exists($menu['items'][$mid]['callback']);
}

/**
 * Returns true when the path is in the active trail.
 */
function menu_in_active_trail($mid) {
  static $trail;

  if (empty($trail)) {
    $trail = menu_get_trail($_GET['q']);
  }

  return in_array($mid, $trail);
}

/**
 * Returns a rendered menu tree.
 */
function menu_tree($pid = 1) {
  static $trail;
  $menu = menu_get_menu();
  $output = '';

  if (empty($trail)) {
    $trail = menu_get_trail($_GET['q']);
  }

  if (isset($menu['visible'][$pid]) && $menu['visible'][$pid]['children']) {

    foreach ($menu['visible'][$pid]['children'] as $mid) {
      $style = (count($menu['visible'][$mid]['children']) ? (menu_in_active_trail($mid)  ? 'expanded' : 'collapsed') : 'leaf');
      $output .= "<li class=\"$style\">";
      $output .= _menu_render_item($mid);
      if (menu_in_active_trail($mid)) {
        $output .= menu_tree($mid);
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
 * Build the menu by querying both modules and the database.
 */
function menu_build() {
  global $_menu;
  global $user;

  // Start from a clean slate.
  $_menu = array();

  // Build a sequential list of all menu items.
  module_invoke_all('link', 'system');

  $_menu['path index'] = array();
  // Set up items array, including default "Navigation" menu.
  $_menu['items'] = array(0 => array(), 1 => array('pid' => 0, 'title' => t('Navigation'), 'weight' => -50, 'visibility' => MENU_SHOW, 'status' => MENU_LOCKED));

  // Menu items not in the DB get temporary negative IDs.
  $temp_mid = -1;

  foreach ($_menu['list'] as $path => $data) {
    $mid = $temp_mid;
    $_menu['items'][$mid] = array('path' => $path, 'title' => $data['title'], 'callback' => $data['callback'], 'weight' => $data['weight'], 'visibility' => $data['visibility'], 'status' => $data['status']);
    $_menu['path index'][$path] = $mid;

    $temp_mid--;
  }

  // Now fetch items from the DB, reassigning menu IDs as needed.
  if (module_exist('menu')) {
    $result = db_query('SELECT * FROM {menu}');
    while ($item = db_fetch_object($result)) {
      // First, add any custom items added by the administrator.
      if ($item->status == MENU_CUSTOM) {
        $_menu['items'][$item->mid] = array('pid' => $item->pid, 'path' => $item->path, 'title' => $item->title, 'callback' => NULL, 'weight' => $item->weight, 'visibility' => MENU_SHOW, 'status' => MENU_CUSTOM);
        $_menu['path index'][$item->path] = $item->mid;
      }
      // Don't display non-custom menu items if no module declared them.
      else if ($old_mid = $_menu['path index'][$item->path]) {
        $_menu['items'][$item->mid] = $_menu['items'][$old_mid];
        unset($_menu['items'][$old_mid]);
        $_menu['path index'][$item->path] = $item->mid;
        // If administrator has changed item position, reflect the change.
        if ($item->status == MENU_MODIFIED) {
          $_menu['items'][$item->mid]['title'] = $item->title;
          $_menu['items'][$item->mid]['pid'] = $item->pid;
          $_menu['items'][$item->mid]['weight'] = $item->weight;
          $_menu['items'][$item->mid]['visibility'] = $item->visibility;
          $_menu['items'][$item->mid]['status'] = $item->status;
        }
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
  menu_build_visible_tree();
}

/**
 * Find all visible items in the menu tree, for ease in displaying to user.
 *
 * Since this is only for display, we only need title, path, and children
 * for each item.
 */
function menu_build_visible_tree($pid = 0) {
  global $_menu;

  if (isset($_menu['items'][$pid])) {
    $parent = $_menu['items'][$pid];

    $children = array();
    if ($parent['children']) {
      usort($parent['children'], '_menu_sort');
      foreach ($parent['children'] as $mid) {
        $children = array_merge($children, menu_build_visible_tree($mid));
      }
    }
    if (($parent['visibility'] == MENU_SHOW) ||
        ($parent['visibility'] == MENU_HIDE_NOCHILD && count($children) > 1)) {
      $_menu['visible'][$pid] = array('title' => $parent['title'], 'path' => $parent['path'], 'children' => $children);
      return array($pid);
    }
    else {
      return $children;
    }
  }

  return array();
}

/**
 * Populate the database representation of the menu.
 *
 * This need only be called at the start of pages that modify the menu.
 */
function menu_rebuild() {
  cache_clear_all();
  menu_build();
  $menu = menu_get_menu();

  $new_items = array();
  foreach ($menu['items'] as $mid => $item) {
    if ($mid < 0 && ($item->status != MENU_LOCKED)) {
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

      $new_items[$mid] = array('mid' => $new_mid, 'pid' => $new_pid, 'path' => $item['path'], 'title' => $item['title'], 'weight' => $item['weight'], 'visibility' => $item['visibility'], 'status' => $item['status']);
    }
  }

  foreach ($new_items as $item) {
    db_query('INSERT INTO {menu} (mid, pid, path, title, weight, visibility, status) VALUES (%d, %d, \'%s\', \'%s\', %d, %d, %d)', $item['mid'], $item['pid'], $item['path'], $item['title'], $item['weight'], $item['visibility'], $item['status']);
  }

  // Rebuild the menu to account for any changes.
  menu_build();
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

function _menu_render_item($mid) {
  $menu = menu_get_menu();

  return l($menu['items'][$mid]['title'], $menu['items'][$mid]['path']);
}


?>
