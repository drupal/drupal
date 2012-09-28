<?php

/**
 * @file
 * Definition of Drupal\Core\Routing\NestedMatcher.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * The nested matcher layers multiple partial matchers together.
 */
class NestedMatcher implements NestedMatcherInterface {

  /**
   * The final matcher.
   *
   * @var Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected $finalMatcher;

  /**
   * An array of PartialMatchers.
   *
   * @var array
   */
  protected $partialMatchers = array();

  /**
   * Array of PartialMatcherInterface objects, sorted.
   *
   * @var type
   */
  protected $sortedMatchers = array();

  /**
   * The initial matcher to match against.
   *
   * @var Drupal\core\Routing\InitialMatcherInterface
   */
  protected $initialMatcher;

  /**
   * The request context.
   *
   * @var Symfony\Component\Routing\RequestContext
   */
  protected $context;

  /**
   * Adds a partial matcher to the matching plan.
   *
   * Partial matchers will be run in the order in which they are added.
   *
   * @param \Drupal\Core\Routing\PartialMatcherInterface $matcher
   *   A partial matcher.
   * @param int $priority
   *   (optional) The priority of the matcher. Higher number matchers will be checked
   *   first. Default to 0.
   *
   * @return NestedMatcherInterface
   *   The current matcher.
   */
  public function addPartialMatcher(PartialMatcherInterface $matcher, $priority = 0) {
    if (empty($this->matchers[$priority])) {
      $this->matchers[$priority] = array();
    }

    $this->matchers[$priority][] = $matcher;
    $this->sortedMatchers = array();
  }

  /**
   * Sets the final matcher for the matching plan.
   *
   * @param \Drupal\Core\Routing\FinalMatcherInterface $final
   *   The matcher that will be called last to ensure only a single route is
   *   found.
   *
   * @return \Drupal\Core\Routing\NestedMatcherInterface
   *   The current matcher.
   */
  public function setFinalMatcher(FinalMatcherInterface $final) {
    $this->finalMatcher = $final;

    return $this;
  }

  /**
   * Sets the first matcher for the matching plan.
   *
   * Partial matchers will be run in the order in which they are added.
   *
   * @param \Drupal\Core\Routing\InitialMatcherInterface $matcher
   *   An initial matcher.  It is responsible for its own configuration and
   *   initial route collection
   *
   * @return \Drupal\Core\Routing\NestedMatcherInterface
   *   The current matcher.
   */
  public function setInitialMatcher(InitialMatcherInterface $initial) {
    $this->initialMatcher = $initial;

    return $this;
  }

  /**
   * Tries to match a request with a set of routes.
   *
   * If the matcher can not find information, it must throw one of the
   * exceptions documented below.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to match.
   *
   * @return array
   *   An array of parameters.
   *
   * @throws ResourceNotFoundException
   *   If no matching resource could be found.
   * @throws MethodNotAllowedException
   *   If a matching resource was found but the request method is not allowed.
   */
  public function matchRequest(Request $request) {
    $collection = $this->initialMatcher->matchRequestPartial($request);

    foreach ($this->getPartialMatchers() as $matcher) {
      if ($collection) {
        $matcher->setCollection($collection);
      }
      $collection = $matcher->matchRequestPartial($request);
    }

    $attributes = $this->finalMatcher->setCollection($collection)->matchRequest($request);

    return $attributes;
  }

  /**
    * Sorts the matchers and flattens them.
    *
    * @return array
    *   An array of RequestMatcherInterface objects.
    */
  public function getPartialMatchers() {
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

  /**
   * Sets the request context.
   *
   * This method is unused. It is here only to satisfy the interface.
   *
   * @param \Symfony\Component\Routing\RequestContext $context
   *   The context
   */
  public function setContext(RequestContext $context) {
    $this->context = $context;
  }

  /**
   * Gets the request context.
   *
   * This method is unused. It is here only to satisfy the interface.
   *
   * @return \Symfony\Component\Routing\RequestContext
   *   The context
   */
  public function getContext() {
    return $this->context;
  }

}
