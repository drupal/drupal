<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\WidgetFactory.
 */

namespace Drupal\field\Plugin\Type\Widget;

use Drupal\Component\Plugin\Factory\DefaultFactory;

/**
 * Factory class for the Widget plugin type.
 */
class WidgetFactory extends DefaultFactory {

  /**
   * Overrides Drupal\Component\Plugin\Factory\DefaultFactory::createInstance().
   */
  public function createInstance($plugin_id, array $configuration) {
    $plugin_class = static::getPluginClass($plugin_id, $this->discovery);
    return new $plugin_class($plugin_id, $this->discovery, $configuration['instance'], $configuration['settings'], $configuration['weight']);
  }
}
