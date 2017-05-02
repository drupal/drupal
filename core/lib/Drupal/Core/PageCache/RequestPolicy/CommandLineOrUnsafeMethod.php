<?php

namespace Drupal\Core\PageCache\RequestPolicy;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Reject when running from the command line or when HTTP method is not safe.
 *
 * The policy denies caching if the request was initiated from the command line
 * interface (drush) or the request method is neither GET nor HEAD (see RFC
 * 2616, section 9.1.1 - Safe Methods).
 */
class CommandLineOrUnsafeMethod implements RequestPolicyInterface {

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    if ($this->isCli() || !$request->isMethodCacheable()) {
      return static::DENY;
    }
  }

  /**
   * Indicates whether this is a CLI request.
   */
  protected function isCli() {
    return PHP_SAPI === 'cli';
  }

}
