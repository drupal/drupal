<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Symfony\Component\HttpFoundation\Request;

/**
 * Provides methods to generate and get session name in tests.
 */
trait SessionTestTrait {

  /**
   * The name of the session cookie.
   */
  protected string $sessionName;

  /**
   * Generates a session cookie name.
   *
   * @param string $data
   *   The data to generate session name.
   */
  protected function generateSessionName($data): void {
    $prefix = (Request::createFromGlobals()->isSecure() ? 'SSESS' : 'SESS');
    $this->sessionName = $prefix . substr(hash('sha256', $data), 0, 32);
  }

  /**
   * Returns the session name in use on the child site.
   *
   * @return string
   *   The name of the session cookie.
   */
  protected function getSessionName(): string {
    return $this->sessionName;
  }

}
