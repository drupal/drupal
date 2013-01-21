<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\Type\Formatter\FormatterFactory.
 */

namespace Drupal\field\Plugin\Type\Formatter;

use Drupal\Component\Plugin\Factory\DefaultFactory;

/**
 * Factory class for the Formatter plugin type.
 */
class FormatterFactory extends DefaultFactory {

  /**
   * Overrides Drupal\Component\Plugin\Factory\DefaultFactory::createInstance().
   */
  public function createInstance($plugin_id, array $configuration) {
    $plugin_class = static::getPluginClass($plugin_id, $this->discovery);
    return new $plugin_class($plugin_id, $this->discovery, $configuration['instance'], $configuration['settings'], $configuration['label'], $configuration['view_mode']);
  }
}
