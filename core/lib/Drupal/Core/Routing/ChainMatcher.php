<?php

/**
 * @file
 * Definition of Drupal\Core\Routing\ChainMatcher.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\RequestContextAwareInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Aggregates multiple matchers together in series.
 *
 * The RequestContext is entirely unused. It's included only to satisfy the
 * interface needed for RouterListener.  Hopefully we can remove it later.
 */
class ChainMatcher implements RequestMatcherInterface, RequestContextAwareInterface {

  /**
   * Array of RequestMatcherInterface objects to be checked in order.
   *
   * @var array
   */
  protected $matchers = array();

  /**
   * Array of RequestMatcherInterface objects, sorted.
   *
   * @var type
   */
  protected $sortedMatchers = array();

  /**
   * The request context for this matcher.
   *
   * This is unused.  It's just to satisfy the interface.
   *
   * @var Symfony\Component\Routing\RequestContext
   */
  protected $context;

  /**
   * Constructor.
   */
  public function __construct() {
    // We will not actually use this object, but it's needed to conform to
    // the interface.
    $this->context = new RequestContext();
  }

  /**
   * Sets the request context.
   *
   * This method is just to satisfy the interface, and is largely vestigial.
   * The request context object does not contain the information we need, so
   * we will use the original request object.
   *
   * @param Symfony\Component\Routing\RequestContext $context
   *   The context.
   */
  public function setContext(RequestContext $context) {
    $this->context = $context;
  }

  /**
   * Gets the request context.
   *
   * This method is just to satisfy the interface, and is largely vestigial.
   * The request context object does not contain the information we need, so
   * we will use the original request object.
   *
   * @return Symfony\Component\Routing\RequestContext
   *   The context.
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * Matches a request against all queued matchers.
   *
   * @param Request $request The request to match
   *
   * @return array An array of parameters
   *
   * @throws \Symfony\Component\Routing\Exception\ResourceNotFoundException
   *   If no matching resource could be found
   * @throws \Symfony\Component\Routing\Exception\MethodNotAllowedException
   *   If a matching resource was found but the request method is not allowed
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
    *   (optional) The priority of the matcher. Higher number matchers will be checked
    *   first. Default to 0.
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
