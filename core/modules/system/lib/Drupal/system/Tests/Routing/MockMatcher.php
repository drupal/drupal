<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Routing\MockMatcher.
 */

namespace Drupal\system\Tests\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

use Closure;

/**
 * A mock matcher that can be configured with any matching logic for testing.
 *
 */
class MockMatcher implements RequestMatcherInterface {

  protected $matcher;

  public function __construct(Closure $matcher) {
    $this->matcher = $matcher;
  }

  public function matchRequest(Request $request) {
    $matcher = $this->matcher;
    return $matcher($request);
  }
}

