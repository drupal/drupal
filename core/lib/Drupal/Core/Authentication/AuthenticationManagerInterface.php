<?php

/**
 * @file
 * Contains Drupal\Core\Authentication\AuthenticationManagerInterface.
 */

namespace Drupal\Core\Authentication;

/**
 * Defines an interface for authentication managers.
 */
interface AuthenticationManagerInterface extends AuthenticationProviderInterface {

  /**
   * Returns the service id of the default authentication provider.
   *
   * @return string
   *   The service id of the default authentication provider.
   */
  public function defaultProviderId();

}
