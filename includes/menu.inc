<?php
// $Id$

/**
 * Register a menu item to the menu system.
 */
function menu($path, $title, $callback = NULL, $help = NULL, $weight = 0, $hidden = 0) {
  global $_list;

  // add the menu to the flat list of menu items:
  $_list[$path] = array("title" => $title, "callback" => $callback, "help" => $help, "weight" => $weight, "hidden" => $hidden);
}

/**
 * Returns the path of the active menu item.
 */
function menu_get_active() {
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

function menu_get_path($path) {
  global $_list;
  static $trail; // cache

  if (empty($trail)) {
    $trail = array();

    while ($path) {
      if ($_list[$path]) {
        array_unshift($trail, $path);
      }

      $path = substr($path, 0, strrpos($path, "/"));
    }
  }

  return $trail;
}

function menu_get_active_title() {
  global $_list;

  if ($path = menu_get_active()) {
    return ucfirst($_list[$path]["title"]);
  }
}

function menu_get_active_help() {
  global $_list;

  if ($path = menu_get_active()) {
    return $_list[$path]["help"];
  }
}

function menu_is_active($path) {

}

function menu_render_item($path) {
  global $_list;

  if ($path == $_GET["q"]) {
    $css = " class=\"active\"";
  }

  return "<a href=\"". url($path) ."\"$css>". t($_list[$path]["title"]) ."</a>";
}

function menu_active_breadcrumb() {

  $links[] = l(t("Home"), "");

  $trail = menu_get_path($_GET["q"]);
  foreach ($trail as $item) {
    $links[] = menu_render_item($item);
  }

  return $links;
}

function _menu_sort($a, $b) {
  global $_list;

  $a = &$_list[$a];
  $b = &$_list[$b];

  return $a["weight"] < $b["weight"] ? -1 : ($a["weight"] > $b["weight"] ? 1 : ($a["title"] < $b["title"] ? -1 : 1));
}

function menu_tree($parent = "") {
  global $_list;
  static $trail;

  if (empty($tail)) {
    $trail = menu_get_path($_GET["q"]);
  }

  if ($_list[$parent]["children"]) {
    $output = "\n<ul>\n";
    usort($_list[$parent]["children"], "_menu_sort");
    foreach ($_list[$parent]["children"] as $item) {
      if ($_list[$item]["hidden"] == 0) {
        $style = ($_list[$item]["children"] ? (in_array($item, $trail)  ? "expanded" : "collapsed") : "leaf");
        $output .= "<li class=\"$style\">";
        $output .= menu_render_item($item);
        if (in_array($item, $trail)) {
          $output .= menu_tree($item);
        }
        $output .= "</li>\n";
      }
    }
    $output .= "</ul>\n";
  }

  return $output;
}


function menu_execute_action() {
  global $_list;

  $path = menu_get_active();

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

?>
