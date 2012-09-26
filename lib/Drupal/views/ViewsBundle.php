<?php

/**
 * @file
 * Definition of Drupal\views\ViewsBundle.
 */

namespace Drupal\views;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Drupal\views\ViewExecutable;

/**
 * Views dependency injection container.
 */
class ViewsBundle extends Bundle {

  /**
   * Overrides Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    foreach (ViewExecutable::getPluginTypes() as $type) {
      if ($type == 'join') {
        $container->register('plugin.manager.views.join', 'Drupal\views\Plugin\Type\JoinManager');
      }
      elseif ($type == 'wizard') {
        $container->register('plugin.manager.views.wizard', 'Drupal\views\Plugin\Type\WizardManager');
      }
      else {
        $container->register("plugin.manager.views.$type", 'Drupal\views\Plugin\Type\PluginManager')
          ->addArgument($type);
      }
    }
  }

}
