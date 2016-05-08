<?php

namespace Drupal\system_test\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a controller to respond the page cache accept header test.
 */
class PageCacheAcceptHeaderController {

  /**
   * Processes a request that will vary with Accept header.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return mixed
   */
  public function content(Request $request) {
    if ($request->getRequestFormat() === 'json') {
      return new CacheableJsonResponse(['content' => 'oh hai this is json']);
    }
    else {
      return new CacheableResponse("<p>oh hai this is html.</p>");
    }
  }

}
