<?php
// $Id: index.php,v 1.56 2003/01/04 23:43:49 dries Exp $

include_once "includes/common.inc";

page_header();

check_php_setting("magic_quotes_gpc", 0);
check_php_setting("register_globals", 1);

if (module_hook(variable_get("site_frontpage", "node"), "page")) {
  module_invoke(variable_get("site_frontpage", "node"), "page");
}
else {
  $theme->header();
  $theme->footer();
}

page_footer();

?>
