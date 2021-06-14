<?php

namespace Drupal\Core\Session;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an interface for session configuration generators.
 */
interface SessionConfigurationInterface {

  /**
   * Determines whether a session identifier is on the request.
   *
   * This method detects whether a session was started during one of the
   * previous requests from the same user agent. Session identifiers are
   * normally passed along using cookies and hence a typical implementation
   * checks whether the session cookie is on the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return bool
   *   TRUE if there is a session identifier on the request.
   */
  public function hasSession(Request $request);

  /**
   * Returns a list of options suitable for passing to the session storage.
   *
   * @see \Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage::__construct()
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   An associative array of session ini settings.
   */
  public function getOptions(Request $request);

}
