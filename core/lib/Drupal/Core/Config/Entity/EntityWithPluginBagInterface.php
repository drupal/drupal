<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Entity\EntityWithPluginBagInterface.
 */

namespace Drupal\Core\Config\Entity;

/**
 * Provides an interface for an object utilizing a plugin bag.
 *
 * @see \Drupal\Component\Plugin\PluginBag
 */
interface EntityWithPluginBagInterface extends ConfigEntityInterface {

  /**
   * Returns the plugin bag used by this entity.
   *
   * @return \Drupal\Component\Plugin\PluginBag
   */
  public function getPluginBag();

}
