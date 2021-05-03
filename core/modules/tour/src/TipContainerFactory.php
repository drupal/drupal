<?php

namespace Drupal\tour;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Plugin\Factory\ContainerFactory;

/**
 * Tip plugin container factory.
 *
 * @todo In Drupal 10, it will not be necessary to accommodate tip plugins that
 *   implement different interfaces. Remove this entire file in
 *    https://drupal.org/node/3195193.
 */
class TipContainerFactory extends ContainerFactory {
  /**
   * {@inheritdoc}
   *
   * Overrides the parent so it is possible to allow tip plugins to extend
   * the deprecated TipPluginInterface, or the current TourTipPluginInterface.
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $plugin_definition = $this->discovery->getDefinition($plugin_id);

    // In 9.x, from 9.2 onwards, a tour tip can have one of two interfaces.
    // This is enforced here instead of getPluginClass(), as that can only
    // enforce the adherence to one interface type.
    if (!in_array($this->interface, ['Drupal\tour\TipPluginInterface', 'Drupal\tour\TourTipPluginInterface'])) {
      throw new PluginException(sprintf('Plugin "%s" must implement interface Drupal\tour\TipPluginInterface or Drupal\tour\TourTipPluginInterface.', $plugin_id));
    }
    $plugin_class = static::getPluginClass($plugin_id, $plugin_definition, null);

    // If the plugin provides a factory method, pass the container to it.
    if (is_subclass_of($plugin_class, 'Drupal\Core\Plugin\ContainerFactoryPluginInterface')) {
      return $plugin_class::create(\Drupal::getContainer(), $configuration, $plugin_id, $plugin_definition);
    }

    // Otherwise, create the plugin directly.
    return new $plugin_class($configuration, $plugin_id, $plugin_definition);
  }
}