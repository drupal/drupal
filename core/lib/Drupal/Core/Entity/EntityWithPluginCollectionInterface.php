<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityWithPluginCollectionInterface.
 */

namespace Drupal\Core\Entity;

/**
 * Provides an interface for an object using a plugin collection.
 *
 * @see \Drupal\Component\Plugin\LazyPluginCollection
 *
 * @ingroup plugin_api
 */
interface EntityWithPluginCollectionInterface extends EntityInterface {

  /**
   * Returns the plugin collections used by this entity.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection[]
   *   An array of plugin collections, keyed by the property name they use to
   *   store their configuration.
   */
  public function getPluginCollections();

}
