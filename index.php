<?php
// $Id$

include_once "includes/common.inc";

page_header();

eval(variable_get("site_frontpage_extra", "") .";");
module_invoke(variable_get("site_frontpage", "node"), "page");

page_footer();

?>
