<?php
// $Id: index.php,v 1.79 2004/04/21 13:56:37 dries Exp $

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
