<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\RedirectDestinationTrait.
 */

namespace Drupal\Core\Routing;

/**
 * Wrapper methods for the Redirect Destination.
 *
 * This utility trait should only be used in application-level code, such as
 * classes that would implement ContainerInjectionInterface. Services registered
 * in the Container should not use this trait but inject the appropriate service
 * directly for easier testing.
 */
trait RedirectDestinationTrait {

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Prepares a 'destination' URL query parameter for use with \Drupal\Core\Url.
   *
   * @see \Drupal\Core\Routing\RedirectDestinationInterface::getAsArray()
   *
   * @return array
   *   An associative array containing the key:
   *   - destination: The value of the current request's 'destination' query
   *     parameter, if present. This can be either a relative or absolute URL.
   *     However, for security, redirection to external URLs is not performed.
   *     If the query parameter isn't present, then the URL of the current
   *     request is returned.
   */
  protected function getDestinationArray() {
    return $this->getRedirectDestination()->getAsArray();
  }

  /**
   * Returns the redirect destination service.
   *
   * @return \Drupal\Core\Routing\RedirectDestinationInterface
   *   The redirect destination helper.
   */
  protected function getRedirectDestination() {
    if (!isset($this->redirectDestination)) {
      $this->redirectDestination = \Drupal::destination();
    }

    return $this->redirectDestination;
  }

  /**
   * Sets the redirect destination service.
   *
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   *
   * @return $this
   */
  public function setRedirectDestination(RedirectDestinationInterface $redirect_destination) {
    $this->redirectDestination = $redirect_destination;

    return $this;
  }

}
