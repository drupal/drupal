<?php
// $Id: module.php,v 1.12 2001/11/01 11:00:46 dries Exp $

include_once "includes/common.inc";

page_header();

module_invoke($mod, "page");

page_footer();

?>
