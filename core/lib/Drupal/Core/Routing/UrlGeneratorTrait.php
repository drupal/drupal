<?php

/**
 * @file
 * Contains Drupal\Core\Routing\UrlGeneratorTrait.
 */

namespace Drupal\Core\Routing;

/**
 * Wrapper methods for the Url Generator.
 *
 * This utility trait should only be used in application-level code, such as
 * classes that would implement ContainerInjectionInterface. Services registered
 * in the Container should not use this trait but inject the appropriate service
 * directly for easier testing.
 */
trait UrlGeneratorTrait {

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Generates a URL or path for a specific route based on the given parameters.
   *
   * @see \Drupal\Core\Routing\UrlGeneratorInterface::generateFromRoute() for
   *   details on the arguments, usage, and possible exceptions.
   *
   * @return string
   *   The generated URL for the given route.
   */
  protected function url($route_name, $route_parameters = array(), $options = array()) {
    return $this->getUrlGenerator()->generateFromRoute($route_name, $route_parameters, $options);
  }

  /**
   * Returns the URL generator service.
   *
   * @return \Drupal\Core\Routing\UrlGeneratorInterface
   *   The URL generator service.
   */
  protected function getUrlGenerator() {
    if (!$this->urlGenerator) {
      $this->urlGenerator = \Drupal::service('url_generator');
    }
    return $this->urlGenerator;
  }

  /**
   * Sets the URL generator service.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $generator
   *   The url generator service.
   *
   * @return $this
   */
  public function setUrlGenerator(UrlGeneratorInterface $generator) {
    $this->urlGenerator = $generator;

    return $this;
  }
}
