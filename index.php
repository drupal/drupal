<?php
// $Id$

include_once "includes/common.inc";

if (isset($_GET["q"])) {
  $mod = arg(0);
}

if (isset($mod) && module_hook($mod, "page")) {
  if ($mod != "admin") {
    page_header();
  }
  module_invoke($mod, "page");
  if ($mod != "admin") {
    page_footer();
  }
}
else {
  page_header();

  check_php_setting("magic_quotes_gpc", 0);

  if (module_hook(variable_get("site_frontpage", "node"), "page")) {
    module_invoke(variable_get("site_frontpage", "node"), "page");
  }
  else {
    theme("header");
    theme("footer");
  }

  page_footer();
}

?>
