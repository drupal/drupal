<?php

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * A NestedMatcher allows for multiple-stage resolution of a route.
 */
interface NestedMatcherInterface extends UrlMatcherInterface {

  /**
   * Adds a partial matcher to the matching plan.
   *
   * Partial matchers will be run in the order in which they are added.
   *
   * @param PartialMatcherInterface $matcher
   *   A partial
   *
   * @return NestedMatcherInterface
   *   The current matcher.
   */
  public function addPartialMatcher(PartialMatcherInterface $matcher);

  /**
   * Sets the final matcher for the matching plan.
   *
   * @param UrlMatcherInterface $final
   *   The matcher that will be called last to ensure only a single route is
   *   found.
   *
   * @return NestedMatcherInterface
   *   The current matcher.
   */
  public function setFinalMatcher(UrlMatcherInterface $final);
}
