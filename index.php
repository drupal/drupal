<?php
// $Id$

include_once "includes/common.inc";

if (!empty($_GET["q"])) {
  if (module_exist("node") && $path = node_get_alias($_GET["q"])) {
    $_GET["q"] = $path;
  }
}
else {
  $_GET["q"] = variable_get("site_frontpage", "node");
}

$mod = arg(0);

drupal_page_header();

check_php_setting("magic_quotes_gpc", 0);

menu_build("system");

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
