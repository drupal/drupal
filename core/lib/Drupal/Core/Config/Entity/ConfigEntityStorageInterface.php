<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Entity\ConfigEntityStorageInterface.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Provides an interface for configuration entity storage.
 */
interface ConfigEntityStorageInterface extends EntityStorageInterface {

  /**
   * Extracts the configuration entity ID from the full configuration name.
   *
   * @param string $config_name
   *   The full configuration name to extract the ID from. E.g.
   *   'views.view.archive'.
   * @param string $config_prefix
   *   The config prefix of the configuration entity. E.g. 'views.view'
   *
   * @return string
   *   The ID of the configuration entity.
   */
  public static function getIDFromConfigName($config_name, $config_prefix);

  /**
   * Creates a configuration entity from storage values.
   *
   * Allows the configuration entity storage to massage storage values before
   * creating an entity.
   *
   * @param array $values
   *   The array of values from the configuration storage.
   *
   * @return ConfigEntityInterface
   *   The configuration entity.
   *
   * @see \Drupal\Core\Entity\EntityStorageBase::mapFromStorageRecords()
   * @see \Drupal\field\FieldStorageConfigStorage::mapFromStorageRecords()
   */
  public function createFromStorageRecord(array $values);

  /**
   * Updates a configuration entity from storage values.
   *
   * Allows the configuration entity storage to massage storage values before
   * updating an entity.
   *
   * @param ConfigEntityInterface $entity
   *   The configuration entity to update.
   * @param array $values
   *   The array of values from the configuration storage.
   *
   * @return ConfigEntityInterface
   *   The configuration entity.
   *
   * @see \Drupal\Core\Entity\EntityStorageBase::mapFromStorageRecords()
   * @see \Drupal\field\FieldStorageConfigStorage::mapFromStorageRecords()
   */
  public function updateFromStorageRecord(ConfigEntityInterface $entity, array $values);

  /**
   * Loads one entity in their original form without overrides.
   *
   * @param mixed $id
   *   The ID of the entity to load.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   An entity object. NULL if no matching entity is found.
   */
  public function loadOverrideFree($id);

  /**
   * Loads one or more entities in their original form without overrides.
   *
   * @param $ids
   *   An array of entity IDs, or NULL to load all entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entity objects indexed by their IDs. Returns an empty array
   *   if no matching entities found.
   */
  public function loadMultipleOverrideFree(array $ids = NULL);

}
