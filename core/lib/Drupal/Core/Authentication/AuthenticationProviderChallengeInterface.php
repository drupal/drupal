<?php

/**
 * @file
 * Contains \Drupal\Core\Authentication\AuthenticationProviderChallengeInterface.
 */

namespace Drupal\Core\Authentication;

use Symfony\Component\HttpFoundation\Request;

/**
 * Generate a challenge when access is denied for unauthenticated users.
 *
 * On a 403 (access denied), if there are no credentials on the request, some
 * authentication methods (e.g. basic auth) require that a challenge is sent to
 * the client.
 */
interface AuthenticationProviderChallengeInterface {

  /**
   * Constructs an exception which is used to generate the challenge.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Exception $previous
   *   The previous exception.
   *
   * @return \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface|NULL
   *   An exception to be used in order to generate an authentication challenge.
   */
  public function challengeException(Request $request, \Exception $previous);

}
