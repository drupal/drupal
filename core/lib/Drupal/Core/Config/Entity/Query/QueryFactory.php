<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Entity\Query\QueryFactory.
 */

namespace Drupal\Core\Config\Entity\Query;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityManager;

/**
 * Provides a factory for creating entity query objects for the config backend.
 */
class QueryFactory {

  /**
   * The config storage used by the config entity query.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $configStorage;

  /**
   * Constructs a QueryFactory object.
   *
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The config storage used by the config entity query.
   */
  public function __construct(StorageInterface $config_storage) {
    return $this->configStorage = $config_storage;
  }

  /**
   * Instantiate a entity query for a certain entity type.
   *
   * @param string $entity_type
   *   The entity type for the query.
   * @param string $conjunction
   *   The operator to use to combine conditions: 'AND' or 'OR'.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager that handles the entity type.
   *
   * @return \Drupal\Core\Config\Entity\Query\Query
   *   An entity query for a specific configuration entity type.
   */
  public function get($entity_type, $conjunction, EntityManager $entity_manager) {
    return new Query($entity_type, $conjunction, $entity_manager, $this->configStorage);
  }

}
