<?php

/**
 * @file
 * Contains \Drupal\editor\EditorBundle.
 */

namespace Drupal\editor;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Editor dependency injection container.
 */
class EditorBundle extends Bundle {

  /**
   * Overrides Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    // Register the plugin manager for our plugin type with the dependency
    // injection container.
    $container->register('plugin.manager.editor', 'Drupal\editor\Plugin\EditorManager');
  }

}
