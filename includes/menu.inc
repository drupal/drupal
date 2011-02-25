<?php

/**
 * Register a menu item with the menu system.
 */
function menu($path, $title, $callback = NULL, $weight = 0, $hidden = 0) {
  global $_list;

  // add the menu to the flat list of menu items:
  $_list[$path] = array("title" => $title, "callback" => $callback, "weight" => $weight, "hidden" => $hidden);
}

/**
 * Returns an array with the menu items that lead to the specied path.
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
 */
function menu_get_active_item() {
  global $_list;
  static $path;

  if (empty($path)) {
    $path = $_GET["q"];

    while ($path && !$_list[$path]) {
      $path = substr($path, 0, strrpos($path, "/"));
    }
  }

  return $path;
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
    if (empty($arg)) {
      return call_user_func($_list[$path]["callback"]);
    }
    else {
      return call_user_func_array($_list[$path]["callback"], explode("/", $arg));
    }
  }
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
 * Returns a rendered menu tree.
 */
function menu_tree($parent = "") {
  global $_list;
  static $trail;

  if (empty($trail)) {
    $trail = menu_get_trail($_GET["q"]);
  }

  if ($_list[$parent]["children"]) {
    $output = "";

    usort($_list[$parent]["children"], "_menu_sort");
    foreach ($_list[$parent]["children"] as $item) {
      /*
      ** Don't render the menu when it is hidden, or when it has no call-back
      ** nor children.  The latter check avoids that useless links are being
      ** rendered.
      */
      if ($_list[$item]["hidden"] == 0 && ($_list[$item]["callback"] || $_list[$item]["children"])) {
        $style = ($_list[$item]["children"] ? (menu_in_active_trail($item)  ? "expanded" : "collapsed") : "leaf");
        $output .= "<li class=\"$style\">";
        $output .= _render_item($item);
        if (menu_in_active_trail($item)) {
          $output .= menu_tree($item);
        }
        $output .= "</li>\n";
      }
    }

    if ($output != '') {
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

  if ($path == $_GET["q"]) {
    $css = " class=\"active\"";
  }

  return "<a href=\"". url($path) ."\"$css>". $_list[$path]["title"] ."</a>";
}


?>
