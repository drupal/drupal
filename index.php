<?php
// $Id: index.php,v 1.52 2001/11/17 15:18:16 kjartan Exp $

include_once "includes/common.inc";

page_header();

eval(variable_get("site_frontpage_extra", "") .";");
module_invoke(variable_get("site_frontpage", "node"), "page");

page_footer();

?>
