<?php
// $Id$

/**
 * @file
 * The PHP page that serves all page requests on a Drupal installation.
 *
 * The routines here dispatch control to the appropriate handler, which then
 * prints the appropriate page.
 */

require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

$return = menu_execute_active_handler();
switch ($return) {
  case MENU_NOT_FOUND:
    drupal_not_found();
    break;
  case MENU_ACCESS_DENIED:
    drupal_access_denied();
    break;
  case MENU_SITE_OFFLINE:
    drupal_site_offline();
    break;
  default:
    // Print any value (including an empty string) except NULL or undefined:
    if (isset($return)) {
      print theme('page', $return);
    }
    break;
}

drupal_page_footer();

