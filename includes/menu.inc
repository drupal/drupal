<?php

/**
 * @defgroup menu Menu system
 * @{
 */

define('MENU_SHOW', 0);
define('MENU_HIDE', 1);
define('MENU_HIDE_NOCHILD', 2);

/** @} */

/**
 * Register a menu item with the menu system.
 *
 * @ingroup menu
 * @param $path Location then menu item refers to.
 * @param $title The title of the menu item to show in the rendered menu.
 * @param $callback The function to call when this is the active menu item.
 * @param $weight Heavier menu items sink down the menu.
 * @param $hidden
 * - MENU_SHOW show the menu (default).
 * - MENU_HIDE hide the menu item, but register a callback.
 * - MENU_HIDE_NOCHILD hide the menu item when it has no children.
 */
function menu($path, $title, $callback = NULL, $weight = 0, $hidden = MENU_SHOW) {
  global $_list;

  // add the menu to the flat list of menu items:
  $_list[$path] = array("title" => $title, "callback" => $callback, "weight" => $weight, "hidden" => $hidden);
}

/**
 * Returns an array with the menu items that lead to the specified path.
 */
function menu_get_trail($path) {
  global $_list;

  $trail = array();

  while ($path) {
    if ($_list[$path]) {
      array_unshift($trail, $path);
    }

    $path = substr($path, 0, strrpos($path, "/"));
  }

  return $trail;
}

/**
 * Returns the path of the active menu item.
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
  global $_list;
  static $stored_path;

  if (is_null($stored_path) || !empty($path)) {
    if (empty($path)) {
      $path = $_GET["q"];
    }
    else {
      $_GET['q'] = $path;
    }

    while ($path && !$_list[$path]) {
      $path = substr($path, 0, strrpos($path, "/"));
    }
    $stored_path = $path;
  }

  return $stored_path;
}

/**
 * Returns the title of the active menu item.
 */
function menu_get_active_title() {
  global $_list;

  if ($path = menu_get_active_item()) {
    return ucfirst($_list[$path]["title"]);
  }
}

/**
 * Returns the help associated with the active menu item.
 */
function menu_get_active_help() {

  if (menu_active_handler_exists()) {
    $path = $_GET["q"];
    $output = "";

    $return = module_invoke_all("help", $path);
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

  $links[] = l(t("Home"), "");

  $trail = menu_get_trail($_GET["q"]);
  foreach ($trail as $item) {
    $links[] = _render_item($item);
  }

  return $links;
}


/**
 * Execute the handler associated with the active menu item.
 */
function menu_execute_active_handler() {
  global $_list;

  $path = menu_get_active_item();

  if ($_list[$path]["callback"]) {
    $arg = substr($_GET["q"], strlen($path) + 1);
    if (isset($arg)) {
      return call_user_func_array($_list[$path]["callback"], explode("/", $arg));
    }
    else {
      return call_user_func($_list[$path]["callback"]);
    }
  }
}

function menu_active_handler_exists() {
  global $_list;

  $path = menu_get_active_item();

  return function_exists($_list[$path]["callback"]);
}

/**
 * Returns true when the path is in the active trail.
 */
function menu_in_active_trail($path) {
  static $trail;

  if (empty($trail)) {
    $trail = menu_get_trail($_GET["q"]);
  }

  return in_array($path, $trail);
}

/**
 * Returns true when the menu has visisble children.
 */
function menu_has_visible_children($item) {
  global $_list;

  if ($_list[$item]['children']) {
    foreach ($_list[$item]['children'] as $child) {
      if ($_list[$child]['hidden'] == MENU_SHOW) {
        return true;
      }
    }
  }

  return false;
}

/**
 * Returns a rendered menu tree.
 */
function menu_tree($parent = "", $hidden = 0) {
  global $_list;
  static $trail;
  $output = "";

  if (empty($trail)) {
    $trail = menu_get_trail($_GET["q"]);
  }

  if (isset($_list[$parent]) && $_list[$parent]["children"]) {

    usort($_list[$parent]["children"], "_menu_sort");
    foreach ($_list[$parent]["children"] as $item) {
      /*
      ** Don't render the menu when it is hidden, or when it has no call-back
      ** nor children.  The latter check avoids that useless links are being
      ** rendered.
      */
      $visible = menu_has_visible_children($item);
      if (($_list[$item]["hidden"] == MENU_SHOW && $_list[$item]["callback"]) ||
         ($_list[$item]["hidden"] == MENU_SHOW && $visible) ||
         ($_list[$item]["hidden"] == MENU_HIDE_NOCHILD && $visible)) {
        $style = ($visible ? (menu_in_active_trail($item)  ? "expanded" : "collapsed") : "leaf");
        $output .= "<li class=\"$style\">";
        $output .= _render_item($item);
        if (menu_in_active_trail($item)) {
          $output .= menu_tree($item);
        }
        $output .= "</li>\n";
      }
      else if ($_list[$item]["hidden"] == MENU_HIDE && $_list[$item]["children"]) {
        $output .= menu_tree($item, 1);
      }
    }

    if ($output != '' && $hidden != MENU_HIDE) {
      $output  = "\n<ul>\n$output\n</ul>\n";
    }
  }

  return $output;
}

/**
 * Query to module to build the menu.
 */
function menu_build($type) {
  /*
  ** Build a sequential list of all menus items.
  */

  module_invoke_all("link", $type);

  /*
  ** Tree-ify the sequential list of menu items by adding each
  ** menu item to the 'children' array of their direct parent.
  */

  global $_list;

  foreach ($_list as $path => $data) {

    /*
    ** Find $path's direct parent:
    */
    $parent = $path;
    do {
      $parent = substr($parent, 0, strrpos($parent, "/"));
    }
    while ($parent && !$_list[$parent]);

    if ($path) {
      $_list[$parent]["children"][] = $path;
    }
  }
}

function _menu_sort($a, $b) {
  global $_list;

  $a = &$_list[$a];
  $b = &$_list[$b];

  return $a["weight"] < $b["weight"] ? -1 : ($a["weight"] > $b["weight"] ? 1 : ($a["title"] < $b["title"] ? -1 : 1));
}

function _render_item($path) {
  global $_list;

  return l($_list[$path]["title"], $path);
}


?>
