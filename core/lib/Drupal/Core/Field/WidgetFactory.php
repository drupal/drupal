<?php

/**
 * @file
 * Contains \Drupal\Core\Field\WidgetFactory.
 */

namespace Drupal\Core\Field;

use Drupal\Component\Plugin\Factory\DefaultFactory;

/**
 * Factory class for the Widget plugin type.
 */
class WidgetFactory extends DefaultFactory {

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    $plugin_definition = $this->discovery->getDefinition($plugin_id);
    $plugin_class = static::getPluginClass($plugin_id, $plugin_definition, $this->interface);
    return new $plugin_class($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings']);
  }
}
