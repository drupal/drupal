<?php
// $Id$

include_once "includes/common.inc";

page_header();

$function = variable_get("site_frontpage", "node") ."_page";
$function();

page_footer();

?>
