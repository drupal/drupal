<?php

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
 *
 * @deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\Link instead.
 *
 * @see https://www.drupal.org/node/2614344
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
   * For details on the arguments, usage, and possible exceptions see
   * \Drupal\Core\Utility\LinkGeneratorInterface::generate().
   *
   * @return \Drupal\Core\GeneratedLink
   *   A GeneratedLink object containing a link to the given route and
   *   parameters and bubbleable metadata.
   *
   * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use
   *   \Drupal\Core\Link::fromTextAndUrl() instead.
   *
   * @see https://www.drupal.org/node/2614344
   * @see \Drupal\Core\Utility\LinkGeneratorInterface::generate()
   */
  protected function l($text, Url $url) {
    @trigger_error(__NAMESPACE__ . "\LinkGeneratorTrait::l() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Link::fromTextAndUrl() instead. See https://www.drupal.org/node/2614344", E_USER_DEPRECATED);
    return $this->getLinkGenerator()->generate($text, $url);
  }

  /**
   * Returns the link generator.
   *
   * @return \Drupal\Core\Utility\LinkGeneratorInterface
   *   The link generator
   *
   * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Inject the
   *   'link_generator' service or use \Drupal\Core\Link instead
   *
   * @see https://www.drupal.org/node/2614344
   */
  protected function getLinkGenerator() {
    @trigger_error(__NAMESPACE__ . "\LinkGeneratorTrait::getLinkGenerator() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Inject the 'link_generator' service or use \Drupal\Core\Link instead. See https://www.drupal.org/node/2614344", E_USER_DEPRECATED);
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
   *
   * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Inject the
   *   'link_generator' service or use \Drupal\Core\Link instead
   *
   * @see https://www.drupal.org/node/2614344
   */
  public function setLinkGenerator(LinkGeneratorInterface $generator) {
    @trigger_error(__NAMESPACE__ . "\LinkGeneratorTrait::setLinkGenerator() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Inject the 'link_generator' service or use \Drupal\Core\Link instead. See https://www.drupal.org/node/2614344", E_USER_DEPRECATED);
    $this->linkGenerator = $generator;

    return $this;
  }

}
