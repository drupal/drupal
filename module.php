<?php
// $Id: module.php,v 1.11 2001/10/20 18:57:07 natrak Exp $

include_once "includes/common.inc";

page_header();

module_invoke($mod, "page");

page_footer();

?>
