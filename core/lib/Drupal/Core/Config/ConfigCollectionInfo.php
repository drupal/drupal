<?php

namespace Drupal\Core\Config;

use Drupal\Component\EventDispatcher\Event;

/**
 * Gets information on all the possible configuration collections.
 */
class ConfigCollectionInfo extends Event {

  /**
   * Configuration collection information keyed by collection name.
   *
   * The value is either the configuration factory override that is responsible
   * for the collection or null if there is not one.
   *
   * @var array
   */
  protected $collections = [];

  /**
   * Adds a collection to the list of possible collections.
   *
   * @param string $collection
   *   Collection name to add.
   * @param \Drupal\Core\Config\ConfigFactoryOverrideInterface $override_service
   *   (optional) The configuration factory override service responsible for the
   *   collection.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown if $collection is equal to
   *   \Drupal\Core\Config\StorageInterface::DEFAULT_COLLECTION.
   */
  public function addCollection($collection, ?ConfigFactoryOverrideInterface $override_service = NULL) {
    if ($collection == StorageInterface::DEFAULT_COLLECTION) {
      throw new \InvalidArgumentException('Can not add the default collection to the ConfigCollectionInfo object');
    }
    $this->collections[$collection] = $override_service;
  }

  /**
   * Gets the list of possible collection names.
   *
   * @param bool $include_default
   *   (Optional) Include the default collection. Defaults to TRUE.
   *
   * @return array
   *   The list of possible collection names.
   */
  public function getCollectionNames($include_default = TRUE) {
    $collection_names = array_keys($this->collections);
    sort($collection_names);
    if ($include_default) {
      array_unshift($collection_names, StorageInterface::DEFAULT_COLLECTION);
    }
    return $collection_names;
  }

  /**
   * Gets the config factory override service responsible for the collection.
   *
   * @param string $collection
   *   The configuration collection.
   *
   * @return \Drupal\Core\Config\ConfigFactoryOverrideInterface|null
   *   The override service responsible for the collection if one exists. NULL
   *   if not.
   */
  public function getOverrideService($collection) {
    return $this->collections[$collection] ?? NULL;
  }

}
