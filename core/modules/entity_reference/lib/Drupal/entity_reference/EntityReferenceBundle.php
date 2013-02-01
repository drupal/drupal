<?php

/**
 * @file
 * Contains \Drupal\entity_reference\EntityReferenceBundle.
 */

namespace Drupal\entity_reference;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Entity Reference dependency injection container.
 */
class EntityReferenceBundle extends Bundle {

  /**
   * Overrides Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    // Register the SelectionPluginManager class with the dependency injection
    // container.
    $container->register('plugin.manager.entity_reference.selection', 'Drupal\entity_reference\Plugin\Type\SelectionPluginManager');
  }
}
