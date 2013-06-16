<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\WidgetFactory.
 */

namespace Drupal\field\Plugin\Type\Widget;

use Drupal\Component\Plugin\Factory\DefaultFactory;

/**
 * Factory class for the Widget plugin type.
 */
class WidgetFactory extends DefaultFactory {

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration) {
    $plugin_definition = $this->discovery->getDefinition($plugin_id);
    $plugin_class = static::getPluginClass($plugin_id, $plugin_definition);
    return new $plugin_class($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings']);
  }
}
