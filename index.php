<?php
// $Id$

include_once "includes/bootstrap.inc";
drupal_page_header();
include_once "includes/common.inc";

drupal_page_header();

fix_gpc_magic();

menu_build("system");

if (menu_active_handler_exists()) {
  $breadcrumb = menu_get_active_breadcrumb();
  array_pop($breadcrumb);
  $title = menu_get_active_title();

  print theme("header");
  print theme("breadcrumb", $breadcrumb);
  if ($help = menu_get_active_help()) {
    $contents = "<small>$help</small><hr />";
  }
  $contents .= menu_execute_active_handler();
  print theme("box", $title, $contents);
  print theme("footer");
}
else {
  $mod = arg(0);

  if (isset($mod) && module_hook($mod, "page")) {
    module_invoke($mod, "page");
  }
  else {
    if (module_hook(variable_get("site_frontpage", "node"), "page")) {
      module_invoke(variable_get("site_frontpage", "node"), "page");
    }
    else {
      print theme("header");
      print theme("footer");
    }
  }
}

drupal_page_footer();

?>
