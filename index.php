<?php
// $Id$

include_once "includes/bootstrap.inc";
drupal_page_header();
include_once "includes/common.inc";

fix_gpc_magic();

$status = menu_execute_active_handler();
switch ($status) {
  case MENU_FOUND:
    break;
  case MENU_DENIED:
    drupal_access_denied();
    break;
  default:
    drupal_not_found();
}

drupal_page_footer();

?>
