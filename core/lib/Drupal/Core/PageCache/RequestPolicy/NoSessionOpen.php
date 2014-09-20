<?php

/**
 * @file
 * Contains \Drupal\Core\PageCache\RequestPolicy\NoSessionOpen.
 */

namespace Drupal\Core\PageCache\RequestPolicy;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A policy allowing delivery of cached pages when there is no session open.
 *
 * Do not serve cached pages to authenticated users, or to anonymous users when
 * $_SESSION is non-empty. $_SESSION may contain status messages from a form
 * submission, the contents of a shopping cart, or other userspecific content
 * that should not be cached and displayed to other users.
 */
class NoSessionOpen implements RequestPolicyInterface {

  /**
   * The name of the session cookie.
   *
   * @var string
   */
  protected $sessionCookieName;

  /**
   * Constructs a new page cache session policy.
   *
   * @param string $session_cookie_name
   *   (optional) The name of the session cookie. Defaults to session_name().
   */
  public function __construct($session_cookie_name = NULL) {
    $this->sessionCookieName = $session_cookie_name ?: session_name();
  }

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    if (!$request->cookies->has($this->sessionCookieName)) {
      return static::ALLOW;
    }
  }

}
