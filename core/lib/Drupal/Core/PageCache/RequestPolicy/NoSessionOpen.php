<?php

/**
 * @file
 * Contains \Drupal\Core\PageCache\RequestPolicy\NoSessionOpen.
 */

namespace Drupal\Core\PageCache\RequestPolicy;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\Session\SessionConfigurationInterface;
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
   * The session configuration.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface
   */
  protected $sessionConfiguration;

  /**
   * Constructs a new page cache session policy.
   *
   * @param \Drupal\Core\Session\SessionConfigurationInterface $session_configuration
   *   The session configuration.
   */
  public function __construct(SessionConfigurationInterface $session_configuration) {
    $this->sessionConfiguration = $session_configuration;
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
