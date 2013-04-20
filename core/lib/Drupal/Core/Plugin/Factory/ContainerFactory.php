<?php
/**
 * @file
 * Contains \Drupal\Core\Plugin\Factory\ContainerFactory.
 */

namespace Drupal\Core\Plugin\Factory;

use Drupal\Component\Plugin\Factory\DefaultFactory;

/**
 * Plugin factory which passes a container to a create method.
 */
class ContainerFactory extends DefaultFactory {

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration) {
    $plugin_definition = $this->discovery->getDefinition($plugin_id);
    $plugin_class = static::getPluginClass($plugin_id, $plugin_definition);
    return $plugin_class::create(\Drupal::getContainer(), $configuration, $plugin_id, $plugin_definition);
  }

}
