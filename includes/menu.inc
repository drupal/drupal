<?php
// $Id$

function menu_add() {
  trigger_error(t("The 'menu_add()' function is deprecated."), E_USER_ERROR);
  // Note that this function will be removed shortly.
}

function menu($path, $title, $callback = NULL, $help = NULL, $weight = 0, $hidden = 0) {
  global $_gmenu;

  if (isset($_gmenu[$path])) {
    if (empty($_gmenu[$path]["callback"])) { // dummy item -> fill in
      $_gmenu[$path] = array("title" => $title, "callback" => $callback, "help" => $help, "weight" => $weight, "hidden" => $hidden, "children" => $_gmenu[$path]["children"]);
      return true;
    }
    else { // real item found
      trigger_error(t("While trying to add '%path' to menu, item already exists.", array("%path" => $path)), E_USER_ERROR);
      return false;
    }
  }
  // if this is reached we need to create a new item
  $_gmenu[$path] = array("title" => $title, "callback" => $callback, "help" => $help, "weight" => $weight, "hidden" => $hidden, "children" => array());
  // does the item have an existing parent?
  $parent = substr($path, 0, strrpos($path, "/"));
  while (!isset($_gmenu[$parent])) {
    $_gmenu[$parent] = array("children" => array($path));
    $path = $parent;
    $parent = substr($parent, 0, strrpos($parent, "/"));
  }
  $_gmenu[$parent]["children"][] = $path;

  return true;
}

function menu_item($in_path) {
  global $_gmenu;
  /*
  ** If you want to theme your links, or if you want to replace them
  ** by an image, this would be the function to customize.
  */
  $trail = menu_trail();
  if (end($trail) == $in_path)
    return t($_gmenu[$in_path]["title"]);
  else
    return "<a href=\"".url($in_path)."\">". t($_gmenu[$in_path]["title"]) ."</a>";
}

function query_string() {
  return $GLOBALS["q"];
}

function menu_trail() {
  global $_gmenu;
  static $_gmenu_trail; // cache

  if (!isset($_gmenu_trail)) {
    $_gmenu_trail = array();
    $cuqs = query_string();

    while (!empty($cuqs) && !isset($_gmenu[$cuqs])) {
      $cuqs = substr($cuqs, 0, strrpos($cuqs, "/"));
    }

    if (!empty($cuqs)) {
      do {
        $_gmenu_trail[] = $cuqs;
        $cuqs = substr($cuqs, 0, strrpos($cuqs, "/"));
      } while (!empty($cuqs) && isset($_gmenu[$cuqs]));
    }
    $_gmenu_trail = array_reverse($_gmenu_trail);
  }

  return $_gmenu_trail;
}

function menu_path() {

  $trail = menu_trail();

  $links = array();

  foreach ($trail as $item) {
    $links[] = menu_item($item);
  }

  return implode(" &gt; ", $links);
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
  return $a["weight"] < $b["weight"] ? -11 : ($a["weight"] > $b["weight"] ? 1 : ($a["name"] < $b["name"] ? -1 : 1));
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
    $arg = substr(query_string(), strlen($selected_menu) + 1);
    if (empty($arg)) {
      return call_user_func($_gmenu[$selected_menu]["callback"]);
    }
    else {
      return call_user_func_array($_gmenu[$selected_menu]["callback"], explode("/", $arg));
    }
  }
}

?>
