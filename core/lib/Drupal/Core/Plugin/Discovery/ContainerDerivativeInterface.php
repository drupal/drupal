<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface.
 */

namespace Drupal\Core\Plugin\Discovery;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derivative fetcher interface to pass the container to static create method.
 */
interface ContainerDerivativeInterface {

  /**
   * Creates an instance of the derivative fetcher.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the fetcher.
   * @param string $plugin_id
   *   The base plugin ID for the plugin ID.
   *
   * @return static
   *   Returns an instance of this fetcher.
   */
  public static function create(ContainerInterface $container, $base_plugin_id);

}
