<?php

/**
 * @file
 * Contains \Drupal\Core\Authentication\AuthenticationProviderInterface.
 */

namespace Drupal\Core\Authentication;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Interface for authentication providers.
 */
interface AuthenticationProviderInterface {

  /**
   * Declares whether the provider applies to a specific request or not.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return bool
   *   TRUE if the provider applies to the passed request, FALSE otherwise.
   */
  public function applies(Request $request);

  /**
   * Authenticates the user.
   *
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   The request object.
   *
   * @return \Drupal\Core\Session\AccountInterface|null
   *   AccountInterface - in case of a successful authentication.
   *   NULL - in case where authentication failed.
   */
  public function authenticate(Request $request);

  /**
   * Performs cleanup tasks at the end of a request.
   *
   * Allow the authentication provider to clean up before the response is sent.
   * This is uses for instance in \Drupal\Core\Authentication\Provider\Cookie to
   * ensure the session gets committed.
   *
   * @param Request $request
   *   The request object.
   */
  public function cleanup(Request $request);

  /**
   * Handles an exception.
   *
   * In case exception has happened we allow authentication providers react.
   * Used in \Drupal\Core\Authentication\Provider\BasicAuth to set up headers to
   * prompt login.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *
   * @return bool
   *   TRUE - exception handled. No need to run through other providers.
   *   FALSE - no actions have been done. Run through other providers.
   */
  public function handleException(GetResponseForExceptionEvent $event);
}
