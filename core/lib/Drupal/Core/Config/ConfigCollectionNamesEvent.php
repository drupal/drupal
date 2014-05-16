<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ConfigCollectionNamesEvent.
 */

namespace Drupal\Core\Config;

use Symfony\Component\EventDispatcher\Event;

/**
 * Wraps a configuration event for event listeners.
 */
class ConfigCollectionNamesEvent extends Event {

  /**
   * Configuration collection names.
   *
   * @var array
   */
  protected $collections = array();

  /**
   * Adds names to the list of possible collections.
   *
   * @param array $collections
   *   Collection names to add.
   */
  public function addCollectionNames(array $collections) {
    $this->collections = array_merge($this->collections, $collections);
  }

  /**
   * Adds a name to the list of possible collections.
   *
   * @param string $collection
   *   Collection name to add.
   */
  public function addCollectionName($collection) {
    $this->addCollectionNames(array($collection));
  }

  /**
   * Gets the list of possible collection names.
   *
   * @return array
   *   The list of possible collection names.
   */
  public function getCollectionNames($include_default = TRUE) {
    sort($this->collections);
    $collections = array_unique($this->collections);
    if ($include_default) {
      array_unshift($collections, StorageInterface::DEFAULT_COLLECTION);
    }
    return $collections;
  }

}
