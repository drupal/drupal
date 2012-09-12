<?php

/**
 * @file
 * Definition of Drupal\views\ViewsBundle.
 */

namespace Drupal\views;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Drupal\views\Plugin\views\PluginBase;

/**
 * Views dependency injection container.
 */
class ViewsBundle extends Bundle {

  /**
   * Overrides Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    foreach (PluginBase::getTypes() as $type) {
      $container->register("plugin.manager.views.$type", 'Drupal\views\Plugin\Type\ViewsPluginManager')
        ->addArgument($type);
    }
  }

}
