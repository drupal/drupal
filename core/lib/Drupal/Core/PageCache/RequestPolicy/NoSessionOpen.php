<?php

namespace Drupal\Core\PageCache\RequestPolicy;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\Session\SessionConfigurationInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A policy allowing delivery of cached pages when there is no session open.
 *
 * Do not serve cached pages to authenticated users, or to anonymous users when
 * the user's session is non-empty. The user's session may contain status
 * messages from a form submission, the contents of a shopping cart, or other
 * user-specific content that should not be cached and displayed to other users.
 */
class NoSessionOpen implements RequestPolicyInterface {

  /**
   * Constructs a new page cache session policy.
   *
   * @param \Drupal\Core\Session\SessionConfigurationInterface $sessionConfiguration
   *   The session configuration.
   */
  public function __construct(
    protected SessionConfigurationInterface $sessionConfiguration,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    if (!$this->sessionConfiguration->hasSession($request)) {
      return static::ALLOW;
    }
  }

}
