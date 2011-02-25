<?php

function menu($path, $title, $callback = NULL, $help = NULL, $weight = 0, $hidden = 0) {
  global $_gmenu;

  if (empty($_gmenu[$path])) {
    // add the menu to the flat list of menu items:
    $_gmenu[$path] = array("title" => $title, "callback" => $callback, "help" => $help, "weight" => $weight, "hidden" => $hidden, "children" => array());

    // find the best matching parent item:
    $parent = substr($path, 0, strrpos($path, "/"));
    while ($parent && !$_gmenu[$parent]) {
      $parent = substr($parent, 0, strrpos($parent, "/"));
    }

    // check if any items need to be lowered:
    if ($parent) {
      foreach ($_gmenu[$parent]["children"] as $key => $item) {
        if (strstr($item, $path)) {
          // remove the item from its parent:
          unset($_gmenu[$parent]["children"][$key]);

          // add the item to its new parent:
          $_gmenu[$path]["children"][] = $item;
        }
      }
    }

    // add the menu to the best matching parent:
    $_gmenu[$parent]["children"][] = $path;
  }
}

function menu_item($in_path) {
  global $_gmenu;
  /*
  ** If you want to theme your links, or if you want to replace them
  ** by an image, this would be the function to customize.
  */
  $trail = menu_trail();
  if (end($trail) == $in_path) {
    $css = " class=\"current\"";
  }

  return "<a href=\"". url($in_path) ."\"$css>". t($_gmenu[$in_path]["title"]) ."</a>";
}

function menu_trail() {
  global $_gmenu;
  static $trail; // cache

  if (empty($trail)) {
    $trail = array();
    $path = $_GET["q"];

    while ($path) {
      if ($_gmenu[$path]) {
        $trail[] = $path;
      }

      $path = substr($path, 0, strrpos($path, "/"));
    }

    $trail = array_reverse($trail);
  }

  return $trail;
}

function menu_path() {

  $trail = menu_trail();

  $links = array();

  foreach ($trail as $item) {
    $links[] = menu_item($item);
  }

  return implode(" &raquo; ", $links);
}

function menu_help() {
  global $_gmenu;
  $path = menu_trail();
  if ($path) {
    $item = array_pop($path);
    $output = $_gmenu[$item]["help"];
  }

  return @$output;
}

function _menu_sort($a, $b) {
  global $_gmenu;
  $a = &$_gmenu[$a];
  $b = &$_gmenu[$b];
  return $a["weight"] < $b["weight"] ? -1 : ($a["weight"] > $b["weight"] ? 1 : ($a["title"] < $b["title"] ? -1 : 1));
}

function menu_tree($parent = "") {
  global $_gmenu;

  if ($_gmenu[$parent]["children"]) {
    $output = "\n<ul>\n";
    usort($_gmenu[$parent]["children"], "_menu_sort");
    foreach ($_gmenu[$parent]["children"] as $item) {
      if ($_gmenu[$item]["hidden"] == 0) {
        $trail = menu_trail($item);
        $style = ($_gmenu[$item]["children"] ? (in_array($item, $trail)  ? "expanded" : "collapsed") : "leaf");
        $output .= "<li class=\"$style\">";
        $output .= menu_item($item);
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

function menu_map($parent = "") {
  global $_gmenu;

  $output = "<ul>";
  usort($_gmenu[$parent]["children"], "_menu_sort");
  foreach ($_gmenu[$parent]["children"] as $item) {
    if ($_gmenu[$item]["hidden"] == 0) {
      $output .= "<li>";
      $output .= menu_item($item);
      $output .= menu_map($item);
      $output .= "</li>";
    }
  }
  $output .= "</ul>";

  return $output;
}

function menu_execute_action() {
  global $_gmenu;
  $trail = menu_trail();
  $selected_menu = array_pop($trail);

  if ($_gmenu[$selected_menu]["callback"]) {
    $arg = substr($_GET["q"], strlen($selected_menu) + 1);
    if (empty($arg)) {
      return call_user_func($_gmenu[$selected_menu]["callback"]);
    }
    else {
      return call_user_func_array($_gmenu[$selected_menu]["callback"], explode("/", $arg));
    }
  }
}

function menu_build($type) {

  // Empty the existing menu tree (if any):
  unset($GLOBALS["_gmenu"]);

  // Build the menu tree:
  module_invoke_all("link", $type);
}

?>
