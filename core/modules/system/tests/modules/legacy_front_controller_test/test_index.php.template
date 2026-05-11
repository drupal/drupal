<?php

/**
 * @file
 * A legacy format front-controller.
 *
 * This does not use Symfony Runtime on purpose so that tests can prove that
 * this format of front-controller will also keep working correctly.
 *
 * All Drupal code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt files in the "core" directory.
 */

declare(strict_types=1);

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once __DIR__ . '/../../../../../../autoload.php';

$kernel = new DrupalKernel('prod', $autoloader);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
