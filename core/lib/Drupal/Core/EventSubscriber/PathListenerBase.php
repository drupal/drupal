<?php

/**
 * @file
 *
 * Definition of Drupal\Core\EventSubscriber\PathListenerBase
 */

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpFoundation\Request;

/**
 * Base class for listeners that are manipulating the path.
 */
abstract class PathListenerBase {

  public function extractPath(Request $request) {
    return $request->attributes->get('system_path') ?: trim($request->getPathInfo(), '/');
  }

  public function setPath(Request $request, $path) {
    $request->attributes->set('system_path', $path);

    // @todo Remove this line once code has been refactored to use the request
    // object directly.
    _current_path($path);
  }

}
