<?php

/**
 * @file
 * Contains \Drupal\hal\HalServiceProvider.
 */

namespace Drupal\hal;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Adds hal+json as known format.
 */
class HalServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->has('http_middleware.negotiation')) {
      $container->getDefinition('http_middleware.negotiation')->addMethodCall('registerFormat', ['hal_json', ['application/hal+json']]);
    }
  }

}

