<?php
// $Id: index.php,v 1.78 2004/04/15 20:49:39 dries Exp $

include_once "includes/bootstrap.inc";
drupal_page_header();
include_once "includes/common.inc";

fix_gpc_magic();

if (menu_active_handler_exists()) {
  menu_execute_active_handler();
}
else {
  drupal_not_found();
}

drupal_page_footer();

?>
