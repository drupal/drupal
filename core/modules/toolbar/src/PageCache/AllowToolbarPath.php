<?php

namespace Drupal\toolbar\PageCache;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Cache policy for the toolbar page cache service.
 *
 * This policy allows caching of requests directed to /toolbar/subtrees/{hash}
 * even for authenticated users.
 */
class AllowToolbarPath implements RequestPolicyInterface {

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    // Note that this regular expression matches the end of pathinfo in order to
    // support multilingual sites using path prefixes.
    if (preg_match('#/toolbar/subtrees/[^/]+(/[^/]+)?$#', $request->getPathInfo())) {
      return static::ALLOW;
    }
  }

}
