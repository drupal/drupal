<?php
// $Id: index.php,v 1.50 2001/10/20 20:58:59 kjartan Exp $

include_once "includes/common.inc";

page_header();

eval(variable_get("site_frontpage_extra", "") .";");
$function = variable_get("site_frontpage", "node") ."_page";
$function();

page_footer();

?>
