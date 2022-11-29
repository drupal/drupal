<?php

namespace Drupal\Core\Plugin\Discovery;

use Drupal\Component\Plugin\Derivative\DeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates additional plugin definitions.
 *
 * The created definitions are based on an existing definition using service
 * injection.
 */
interface ContainerDeriverInterface extends DeriverInterface {

  /**
   * Creates a new class instance.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the fetcher.
   * @param string $base_plugin_id
   *   The base plugin ID for the plugin ID.
   *
   * @return static
   *   Returns an instance of this fetcher.
   */
  public static function create(ContainerInterface $container, $base_plugin_id);

}
