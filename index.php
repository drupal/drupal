<?php
// $Id: index.php,v 1.51 2001/11/01 11:00:46 dries Exp $

include_once "includes/common.inc";

page_header();

eval(variable_get("site_frontpage_extra", "") .";");
$function = variable_get("site_frontpage", "node") ."_page";
$function();

page_footer();

?>
