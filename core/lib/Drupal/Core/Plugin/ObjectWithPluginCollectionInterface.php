<?php

namespace Drupal\Core\Plugin;

/**
 * Provides an interface for an object using a plugin collection.
 *
 * @see \Drupal\Component\Plugin\LazyPluginCollection
 *
 * @ingroup plugin_api
 *
 * Entities that need this interface should implement
 * \Drupal\Core\Entity\EntityWithPluginCollectionInterface, which extends this.
 */
interface ObjectWithPluginCollectionInterface {

  /**
   * Gets the plugin collections used by this object.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection[]
   *   An array of plugin collections, keyed by the property name they use to
   *   store their configuration.
   */
  public function getPluginCollections();

}
