<?php
// $Id$

include_once "includes/common.inc";

page_header();

eval(variable_get("site_frontpage_extra", "") .";");
$function = variable_get("site_frontpage", "node") ."_page";
$function();

page_footer();

?>
