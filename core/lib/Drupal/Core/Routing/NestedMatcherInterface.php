<?php

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
   * @param InitialMatcherInterface $matcher
   *   An initial matcher.  It is responsible for its own configuration and
   *   initial route collection
   *
   * @return NestedMatcherInterface
   *   The current matcher.
   */
  public function setInitialMatcher(InitialMatcherInterface $initial);

  /**
   * Adds a partial matcher to the matching plan.
   *
   * Partial matchers will be run in the order in which they are added.
   *
   * @param PartialMatcherInterface $matcher
   *   A partial matcher.
   *
   * @return NestedMatcherInterface
   *   The current matcher.
   */
  public function addPartialMatcher(PartialMatcherInterface $matcher);

  /**
   * Sets the final matcher for the matching plan.
   *
   * @param FinalMatcherInterface $final
   *   The matcher that will be called last to ensure only a single route is
   *   found.
   *
   * @return NestedMatcherInterface
   *   The current matcher.
   */
  public function setFinalMatcher(FinalMatcherInterface $final);
}
