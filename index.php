<?php
// $Id: index.php,v 1.53 2002/04/14 19:34:00 kjartan Exp $

include_once "includes/common.inc";

page_header();

if (module_hook(variable_get("site_frontpage", "node"), "page")) {
  eval(variable_get("site_frontpage_extra", "") .";");
  module_invoke(variable_get("site_frontpage", "node"), "page");
}
else {
  $theme->header();
  $theme->footer();
}

page_footer();

?>
