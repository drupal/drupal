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

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;

/**
 * Root directory of Drupal installation.
 */
define('DRUPAL_ROOT', getcwd());
// Bootstrap the lowest level of what we need.
require_once DRUPAL_ROOT . '/core/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);

// A request object from the HTTPFoundation to tell us about the request.
$request = Request::createFromGlobals();

// Set the global $request object.  This is a temporary measure to
// keep legacy utility functions working.  It should be moved to a dependency
// injection container at some point.
request($request);


drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

$dispatcher = new EventDispatcher();
$resolver = new ControllerResolver();

$kernel = new DrupalKernel($dispatcher, $resolver);
$kernel->handle($request)->send();
