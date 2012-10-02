<?php

/**
 * @file
 * Definition of Drupal\Core\Routing\NestedMatcherInterface.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * A NestedMatcher allows for multiple-stage resolution of a route.
 */
interface NestedMatcherInterface extends RequestMatcherInterface {

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
  public function setInitialMatcher(InitialMatcherInterface $initial);

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
  public function addPartialMatcher(PartialMatcherInterface $matcher, $priority = 0);

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
  public function setFinalMatcher(FinalMatcherInterface $final);
}
