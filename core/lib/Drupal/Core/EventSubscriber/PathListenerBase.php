<?php

/**
 * @file
 * Definition of Drupal\Core\EventSubscriber\PathListenerBase.
 */

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpFoundation\Request;

/**
 * Base class for listeners that are manipulating the path.
 */
abstract class PathListenerBase {

  public function extractPath(Request $request) {
    $path = $request->attributes->get('_system_path');
    return isset($path) ? $path : trim($request->getPathInfo(), '/');
  }

  public function setPath(Request $request, $path) {
    $request->attributes->set('_system_path', $path);
  }
}
