<?php
// $Id: index.php,v 1.54 2002/10/18 10:05:46 dries Exp $

include_once "includes/common.inc";

page_header();

if (module_hook(variable_get("site_frontpage", "node"), "page")) {
  module_invoke(variable_get("site_frontpage", "node"), "page");
}
else {
  $theme->header();
  $theme->footer();
}

page_footer();

?>
