<?php

namespace Drupal\Core\Routing;

/**
 * Provides an interface for redirect destinations.
 */
interface RedirectDestinationInterface {

  /**
   * Prepares a 'destination' URL query parameter for use with \Drupal\Core\Url.
   *
   * Used to direct the user back to the referring page after completing a form.
   * By default the current URL is returned. If a destination exists in the
   * current request, that destination is returned. As such, a destination can
   * persist across multiple pages.
   *
   * @return array
   *   An associative array containing the key:
   *   - destination: The value of the current request's 'destination' query
   *     parameter, if present. This can be either a relative or absolute URL.
   *     However, for security, redirection to external URLs is not performed.
   *     If the query parameter isn't present, then the URL of the current
   *     request is returned.
   *
   * @see \Drupal\Core\EventSubscriber\RedirectResponseSubscriber::checkRedirectUrl()
   * @ingroup form_api
   */
  public function getAsArray();

  /**
   * Gets the destination as a path.
   *
   * To convert to a URL suitable for
   * \Symfony\Component\HttpFoundation\RedirectResponse::__construct() use
   * @code
   * \Drupal\Core\Url::fromUserInput(\Drupal::destination()->get())->setAbsolute()->toString()
   * @endcode
   *
   * @return string
   */
  public function get();

  /**
   * Sets the destination as URL.
   *
   * This method should be used really rarely, for example views uses it, in
   * order to override all destination calls in all of its rendering.
   *
   * @param string $new_destination
   *   The new destination.
   *
   * @return $this
   */
  public function set($new_destination);

}
