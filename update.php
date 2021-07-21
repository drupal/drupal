<?php

/**
 * @file
 * The PHP page that handles updating the Drupal installation.
 *
 * All Drupal code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt files in the "core" directory.
 */

use Drupal\Core\Test\UserAgent;
use Drupal\Core\Update\UpdateKernel;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once 'autoload.php';

// Disable garbage collection during test runs. Under certain circumstances the
// update path will create so many objects that garbage collection causes
// segmentation faults.
$request = Request::createFromGlobals();
if (UserAgent::validate($request)) {
  gc_collect_cycles();
  gc_disable();
}

$kernel = new UpdateKernel('prod', $autoloader, FALSE);

$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
