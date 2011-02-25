<?php

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
