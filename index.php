<?php

include_once "includes/common.inc";

page_header();


//$theme->header();

$function = variable_get("site_frontpage", "node") ."_page";
$function();

//$theme->footer();

page_footer();

?>
