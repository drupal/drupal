<?php
// $Id: index.php,v 1.68 2003/09/30 17:00:49 dries Exp $

include_once "includes/common.inc";

drupal_page_header();

check_php_setting("magic_quotes_gpc", 0);

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
