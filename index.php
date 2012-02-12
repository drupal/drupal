<?php

/**
 * @file
 * The PHP page that serves all page requests on a Drupal installation.
 *
 * The routines here dispatch control to the appropriate handler, which then
 * prints the appropriate page.
 *
 * All Drupal code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt files in the "core" directory.
 */

/**
 * Root directory of Drupal installation.
 */
define('DRUPAL_ROOT', getcwd());

use Symfony\Component\HttpFoundation\Request;

// Bootstrap the lowest level of what we need.
require_once DRUPAL_ROOT . '/core/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// A request object from the HTTPFoundation to tell us about the request.
$request = Request::createFromGlobals();
// Run our router, get a response.
$response = router_execute_active_handler($request);
// Output response.
$response->send();
