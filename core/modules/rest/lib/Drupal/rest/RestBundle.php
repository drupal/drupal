<?php

/**
 * @file
 * Definition of Drupal\rest\RestBundle.
 */

namespace Drupal\rest;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Rest dependency injection container.
 */
class RestBundle extends Bundle {

  /**
   * Overrides Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    // Register the resource manager class with the dependency injection
    // container.
    $container->register('plugin.manager.rest', 'Drupal\rest\Plugin\Type\ResourcePluginManager');
  }
}
