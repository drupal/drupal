<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\LinkGeneratorTrait.
 *
 * @deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\Link instead.
 */

namespace Drupal\Core\Routing;


use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;

/**
 * Wrapper methods for the Link Generator.
 *
 * This utility trait should only be used in application-level code, such as
 * classes that would implement ContainerInjectionInterface. Services registered
 * in the Container should not use this trait but inject the appropriate service
 * directly for easier testing.
 */
trait LinkGeneratorTrait {

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * Renders a link to a route given a route name and its parameters.
   *
   * @see \Drupal\Core\Utility\LinkGeneratorInterface::generate() for details
   *   on the arguments, usage, and possible exceptions.
   *
   * @return \Drupal\Core\GeneratedLink
   *   A GeneratedLink object containing a link to the given route and
   *   parameters and bubbleable metadata.
   *
   * @deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Link instead.
   */
  protected function l($text, Url $url) {
    return $this->getLinkGenerator()->generate($text, $url);
  }

  /**
   * Returns the link generator.
   *
   * @return \Drupal\Core\Utility\LinkGeneratorInterface
   *   The link generator
   */
  protected function getLinkGenerator() {
    if (!isset($this->linkGenerator)) {
      $this->linkGenerator = \Drupal::service('link_generator');
    }
    return $this->linkGenerator;
  }

  /**
   * Sets the link generator service.
   *
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $generator
   *   The link generator service.
   *
   * @return $this
   */
  public function setLinkGenerator(LinkGeneratorInterface $generator) {
    $this->linkGenerator = $generator;

    return $this;
  }
}
