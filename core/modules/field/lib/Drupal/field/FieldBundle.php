<?php

/**
 * @file
 * Contains Drupal\field\FieldBundle.
 */

namespace Drupal\field;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Field dependency injection container.
 */
class FieldBundle extends Bundle {

  /**
   * Overrides Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    // Register the plugin managers for our plugin types with the dependency injection container.
    $container->register('plugin.manager.field.widget', 'Drupal\field\Plugin\Type\Widget\WidgetPluginManager');
    $container->register('plugin.manager.field.formatter', 'Drupal\field\Plugin\Type\Formatter\FormatterPluginManager');
  }

}
