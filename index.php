<?php
// $Id: index.php,v 1.49 2001/10/20 18:57:07 kjartan Exp $

include_once "includes/common.inc";

page_header();

$function = variable_get("site_frontpage", "node") ."_page";
$function();

page_footer();

?>
