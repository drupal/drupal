<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityWithPluginBagsInterface.
 */

namespace Drupal\Core\Entity;

/**
 * Provides an interface for an object utilizing a plugin bag.
 *
 * @see \Drupal\Component\Plugin\PluginBag
 *
 * @ingroup plugin_api
 */
interface EntityWithPluginBagsInterface extends EntityInterface {

  /**
   * Returns the plugin bags used by this entity.
   *
   * @return \Drupal\Component\Plugin\PluginBag[]
   *   An array of plugin bags, keyed by the property name they use to store
   *   their configuration.
   */
  public function getPluginBags();

}
