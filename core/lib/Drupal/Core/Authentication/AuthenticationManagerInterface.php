<?php

/**
 * @file
 * Contains \Drupal\Core\Authentication\AuthenticationManagerInterface
 */

namespace Drupal\Core\Authentication;

/**
 * Defines an interface for authentication managers.
 */
interface AuthenticationManagerInterface {

  /**
   * Adds a provider to the array of registered providers.
   *
   * @param \Drupal\Core\Authentication\AuthenticationProviderInterface $provider
   *   The provider object.
   * @param string $provider_id
   *   Identifier of the provider.
   * @param int $priority
   *   (optional) The provider's priority.
   * @param bool $global
   *   (optional) TRUE if the provider is to be applied globally on all routes.
   *   Defaults to FALSE.
   */
  public function addProvider(AuthenticationProviderInterface $provider, $provider_id, $priority = 0, $global = FALSE);

}
