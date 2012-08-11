<?php

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

/**
 * Description of ChainMatcher
 */
class ChainMatcher implements RequestMatcherInterface {

  /**
   * Array of RequestMatcherInterface objects to be checked in order.
   *
   * @var array
   */
  protected $matchers = array();

  /**
   * Array of RequestMatcherInterface objects, sorted.
   * @var type
   */
  protected $sortedMatchers = array();

  /**
   * Matches a request against all queued matchers.
   *
   * @param Request $request The request to match
   *
   * @return array An array of parameters
   *
   * @throws ResourceNotFoundException If no matching resource could be found
   * @throws MethodNotAllowedException If a matching resource was found but the request method is not allowed
   */
  public function matchRequest(Request $request) {
    $methodNotAllowed = null;

    foreach ($this->all() as $matcher) {
      try {
        return $matcher->matchRequest($request);
      } catch (ResourceNotFoundException $e) {
        // Needs special care
      } catch (MethodNotAllowedException $e) {
        $methodNotAllowed = $e;
      }
    }

    throw $methodNotAllowed ?: new ResourceNotFoundException("None of the matchers in the chain matched this request.");
  }

  /**
    * Adds a Matcher to the index.
    *
    * @param MatcherInterface $matcher
    *   The matcher to add.
    * @param int $priority
    *   The priority of the matcher. Higher number matchers will be checked
    *   first.
    */
  public function add(RequestMatcherInterface $matcher, $priority = 0) {
    if (empty($this->matchers[$priority])) {
      $this->matchers[$priority] = array();
    }

    $this->matchers[$priority][] = $matcher;
    $this->sortedMatchers = array();
  }

  /**
    * Sorts the matchers and flattens them.
    *
    * @return array
    *   An array of RequestMatcherInterface objects.
    */
  public function all() {
    if (empty($this->sortedMatchers)) {
      $this->sortedMatchers = $this->sortMatchers();
    }

    return $this->sortedMatchers;
  }

  /**
    * Sort matchers by priority.
    *
    * The highest priority number is the highest priority (reverse sorting).
    *
    * @return \Symfony\Component\Routing\RequestMatcherInterface[]
    *   An array of Matcher objects in the order they should be used.
    */
  protected function sortMatchers() {
    $sortedMatchers = array();
    krsort($this->matchers);

    foreach ($this->matchers as $matchers) {
      $sortedMatchers = array_merge($sortedMatchers, $matchers);
    }

    return $sortedMatchers;
  }

}
