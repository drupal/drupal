<?php

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Wrapper methods for the Url Generator.
 *
 * This utility trait should only be used in application-level code, such as
 * classes that would implement ContainerInjectionInterface. Services registered
 * in the Container should not use this trait but inject the appropriate service
 * directly for easier testing.
 *
 * @deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\Url instead.
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
   * For details on the arguments, usage, and possible exceptions see
   * \Drupal\Core\Routing\UrlGeneratorInterface::generateFromRoute().
   *
   * @return string
   *   The generated URL for the given route.
   *
   * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0.
   *   Use \Drupal\Core\Url::fromUri() instead.
   *
   * @see \Drupal\Core\Routing\UrlGeneratorInterface::generateFromRoute()
   */
  protected function url($route_name, $route_parameters = [], $options = []) {
    @trigger_error(__NAMESPACE__ . "\UrlGeneratorTrait::url() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Url::fromUri() instead. See https://www.drupal.org/node/2614344", E_USER_DEPRECATED);
    return $this->getUrlGenerator()->generateFromRoute($route_name, $route_parameters, $options);
  }

  /**
   * Returns a redirect response object for the specified route.
   *
   * @param string $route_name
   *   The name of the route to which to redirect.
   * @param array $route_parameters
   *   (optional) Parameters for the route.
   * @param array $options
   *   (optional) An associative array of additional options.
   * @param int $status
   *   (optional) The HTTP redirect status code for the redirect. The default is
   *   302 Found.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object that may be returned by the controller.
   *
   * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0.
   *   Use new RedirectResponse(Url::fromRoute()) instead.
   */
  protected function redirect($route_name, array $route_parameters = [], array $options = [], $status = 302) {
    @trigger_error(__NAMESPACE__ . "\UrlGeneratorTrait::redirect() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use new RedirectResponse(Url::fromRoute()) instead. See https://www.drupal.org/node/2614344", E_USER_DEPRECATED);
    $options['absolute'] = TRUE;
    $url = $this->getUrlGenerator()->generateFromRoute($route_name, $route_parameters, $options);
    return new RedirectResponse($url, $status);
  }

  /**
   * Returns the URL generator service.
   *
   * @return \Drupal\Core\Routing\UrlGeneratorInterface
   *   The URL generator service.
   *
   * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0.
   *   Use the url_generator service instead.
   */
  protected function getUrlGenerator() {
    @trigger_error(__NAMESPACE__ . "\UrlGeneratorTrait::getUrlGenerator() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use the url_generator service instead. See https://www.drupal.org/node/2614344", E_USER_DEPRECATED);
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
   *
   * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0.
   */
  public function setUrlGenerator(UrlGeneratorInterface $generator) {
    @trigger_error(__NAMESPACE__ . "\UrlGeneratorTrait::setUrlGenerator() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/2614344", E_USER_DEPRECATED);
    $this->urlGenerator = $generator;

    return $this;
  }

}
