<?php
// $Id: index.php,v 1.69 2003/10/31 19:34:01 dries Exp $

include_once "includes/common.inc";

drupal_page_header();

fix_gpc_magic();

menu_build("system");

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

drupal_page_footer();

?>
