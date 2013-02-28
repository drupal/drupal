<?php

/**
 * @file
 * Definition of Drupal\layout\LayoutBundle.
 */

namespace Drupal\Layout;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Layout dependency injection container.
 */
class LayoutBundle extends Bundle {

  /**
   * Overrides Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    // Register the LayoutManager class with the dependency injection container.
    $container->register('plugin.manager.layout', 'Drupal\layout\Plugin\Type\LayoutManager')
      ->addArgument('%container.namespaces%');
  }
}
