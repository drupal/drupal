<?php
// $Id$

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
