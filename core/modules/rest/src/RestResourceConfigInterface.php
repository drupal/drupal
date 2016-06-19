<?php

namespace Drupal\rest;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Defines a configuration entity to store enabled REST resources.
 */
interface RestResourceConfigInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Granularity value for per-method configuration.
   */
  const METHOD_GRANULARITY = 'method';

  /**
   * Retrieves the REST resource plugin.
   *
   * @return \Drupal\rest\Plugin\ResourceInterface
   *   The resource plugin
   */
  public function getResourcePlugin();

  /**
   * Retrieves a list of supported HTTP methods.
   *
   * @return string[]
   *   A list of supported HTTP methods.
   */
  public function getMethods();

  /**
   * Retrieves a list of supported authentication providers.
   *
   * @param string $method
   *   The request method e.g GET or POST.
   *
   * @return string[]
   *   A list of supported authentication provider IDs.
   */
  public function getAuthenticationProviders($method);

  /**
   * Retrieves a list of supported response formats.
   *
   * @param string $method
   *   The request method e.g GET or POST.
   *
   * @return string[]
   *   A list of supported format IDs.
   */
  public function getFormats($method);

}
