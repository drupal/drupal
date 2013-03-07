<?php

/**
 * @file
 * Contains \Drupal\views_ui\ViewsBundle.
 */

namespace Drupal\views_ui;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Views UI dependency injection container.
 */
class ViewsUiBundle extends Bundle {

  /**
   * Overrides \Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    $container->register('paramconverter.views_ui', 'Drupal\views_ui\ParamConverter\ViewUIConverter')
      ->addArgument(new Reference('user.tempstore'))
      ->addTag('paramconverter');
  }

}
