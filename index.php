<?php
// $Id: index.php,v 1.76 2003/11/25 19:26:20 dries Exp $

include_once "includes/bootstrap.inc";
drupal_page_header();
include_once "includes/common.inc";

fix_gpc_magic();

menu_build("system");

if (menu_active_handler_exists()) {
  menu_execute_active_handler();
}
else {
  print theme("page", "");
}

drupal_page_footer();

?>
