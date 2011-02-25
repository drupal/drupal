<?php

include_once "includes/common.inc";

if (isset($_GET["q"])) {
  $mod = arg(0);
}
else {
  $_GET["q"] = variable_get("site_frontpage", "node");
  $mod = arg(0);
}

if (isset($mod) && module_hook($mod, "page")) {
  drupal_page_header();
  module_invoke($mod, "page");
  drupal_page_footer();
}
else {
  drupal_page_header();
  check_php_setting("magic_quotes_gpc", 0);

  if (module_hook(variable_get("site_frontpage", "node"), "page")) {
    module_invoke(variable_get("site_frontpage", "node"), "page");
  }
  else {
    theme("header");
    theme("footer");
  }
  drupal_page_footer();
}

?>
