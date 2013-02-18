<?php

/**
 * @file
 * Contains \Drupal\tour\TourBundle.
 */

namespace Drupal\tour;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Tour dependency injection container.
 */
class TourBundle extends Bundle {

  /**
   * Overrides \Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    // Register the plugin manager for our plugin type with the dependency
    // injection container.
    $container->register('plugin.manager.tour', 'Drupal\tour\TourManager');
  }
}
