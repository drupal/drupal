<?php

function menu_trail() {

  global $QUERY_STRING;
  static $trail = NULL;

  /*
  ** Retrieve the currently active link.
  */

  if (empty($trail)) {

    $path = array();
    $item = db_fetch_object(db_query("SELECT * FROM menu WHERE link LIKE '%%%s'", $QUERY_STRING));

    /*
    ** Compile an array of menu objects that represent the path to
    ** the root link.
    */

    if ($item) {
      $path[] = $item;

      while ($item->parent) {
        $result = db_query("SELECT * FROM menu WHERE name = '%s' ORDER BY weight, name", $item->parent);
        if ($item = db_fetch_object($result)) {
          $path[] = $item;
        }
      }
    }

    $trail = array_reverse($path);
  }

  return $trail;
}

function menu_item($item) {

  /*
  ** If you want to theme your links, or if you want to replace them
  ** by an image, this would be the function to customize.
  */

  if (stristr(request_uri(), $item->link) == $item->link) {
    return t($item->name);
  }
  else if ($item->title) {
    return "<a href=\"$item->link\" title=\"". t($item->title) ."\">". t($item->name) ."</a>";
  }
  else {
    return "<a href=\"$item->link\">". t($item->name) ."</a>";
  }
}

function menu_path() {

  $path = menu_trail();

  $links = array();

  foreach ($path as $item) {
    $links[] = menu_item($item);
  }

  return implode(" &gt; ", $links);
}

function menu_menu_row($parent = "") {

  $links = array();

  $result = db_query("SELECT * FROM menu WHERE parent = '%s' ORDER BY weight, name", $parent);
  while ($menu = db_fetch_object($result)) {
    $links[] = menu_item($menu);
  }

  return $links;
}

function menu_menu() {
  $path = menu_trail();
  if ($path) {
    $item = array_pop($path);
    $output = implode(" &middot; ", menu_menu_row($item->name));
  }

  return $output;
}

function menu_help() {
  $path = menu_trail();
  if ($path) {
    $item = array_pop($path);
    $output = $item->help;
  }

  return $output;
}

function menu_tree($parent = "", $overview = 0) {

  $result = db_query("SELECT * FROM menu WHERE parent = '%s' AND overview = '%d' ORDER BY weight, name", $parent, $overview);

  if (db_num_rows($result)) {
    $output = "<ul>";
    while ($item = db_fetch_object($result)) {
      $output .= "<li>". menu_item($item) ."</li>";
      $output .= menu_tree($item->name, 1);
    }
    $output .= "</ul>";
  }

  return $output;
}

function menu_add($name, $link, $title = NULL, $help = NULL, $parent = NULL, $weight = 1, $overview = 0) {
  if (!db_result(db_query("SELECT name FROM menu WHERE link = '%s'", $link))) {
    db_query("INSERT INTO menu (name, link, title, help, parent, weight, overview) VALUES ('%s', '%s', '%s', '%s', '%s', '%d', '%d')", $name, $link, $title, $help, $parent, $weight, $overview);
  }
}

?>