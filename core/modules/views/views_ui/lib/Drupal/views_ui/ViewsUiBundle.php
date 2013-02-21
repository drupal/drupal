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
    $container->register('views_ui.controller', 'Drupal\views_ui\Routing\ViewsUIController')
      ->addArgument(new Reference('plugin.manager.entity'))
      ->addArgument(new Reference('views.views_data'))
      ->addArgument(new Reference('user.tempstore'));
    $container->register('views_ui.form.basic_settings', 'Drupal\views_ui\Form\BasicSettingsForm')
      ->addArgument(new Reference('config.factory'));
    $container->register('views_ui.form.advanced_settings', 'Drupal\views_ui\Form\AdvancedSettingsForm')
      ->addArgument(new Reference('config.factory'));
    $container->register('views_ui.form.breakLock', 'Drupal\views_ui\Form\BreakLockForm')
      ->addArgument(new Reference('plugin.manager.entity'))
      ->addArgument(new Reference('user.tempstore'));
    $container->register('paramconverter.views_ui', 'Drupal\views_ui\ParamConverter\ViewUIConverter')
      ->addArgument(new Reference('user.tempstore'))
      ->addTag('paramconverter');
  }

}
