<?php

namespace Drupal\Tests\system\Functional\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * A mock matcher that can be configured with any matching logic for testing.
 */
class MockMatcher implements RequestMatcherInterface {

  /**
   * The matcher being tested.
   */
  protected $matcher;

  /**
   * Constructs a MockMatcher object.
   *
   * @param \Closure $matcher
   *   An anonymous function that will be used for the matchRequest() method.
   */
  public function __construct(\Closure $matcher) {
    $this->matcher = $matcher;
  }

  /**
   * {@inheritdoc}
   */
  public function matchRequest(Request $request) {
    $matcher = $this->matcher;
    return $matcher($request);
  }

}
