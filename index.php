<?php
// $Id: index.php,v 1.70 2003/11/08 09:56:21 dries Exp $

include_once "includes/common.inc";

drupal_page_header();

fix_gpc_magic();

menu_build("system");

if (menu_active_handler_exists()) {
  $breadcrumb = menu_get_active_breadcrumb();
  array_pop($breadcrumb);
  $title = menu_get_active_title();

  theme("header");
  theme("breadcrumb", $breadcrumb);
  if ($help = menu_get_active_help()) {
    $contents = "<small>$help</small><hr />";
  }
  $contents .= menu_execute_active_handler();
  theme("box", $title, $contents);
  theme("footer");
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
      theme("header");
      theme("footer");
    }
  }
}

drupal_page_footer();

?>
