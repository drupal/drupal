<?php

/**
 * @file
 * Definition of Drupal\Core\Config\Entity\ConfigEntityInterface.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the interface common for all configuration entities.
 */
interface ConfigEntityInterface extends EntityInterface {

  /**
   * Returns the original ID.
   *
   * @return string|null
   *   The original ID, if any.
   */
  public function getOriginalId();

  /**
   * Sets the original ID.
   *
   * @param string $id
   *   The new ID to set as original ID.
   *
   * @return self
   */
  public function setOriginalId($id);

  /**
   * Enables the configuration entity.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The configuration entity.
   */
  public function enable();

  /**
   * Disables the configuration entity.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The configuration entity.
   */
  public function disable();

  /**
   * Sets the status of the configuration entity.
   *
   * @param bool $status
   *   The status of the configuration entity.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The class instance that this method is called on.
   */
  public function setStatus($status);

  /**
   * Sets the status of the isSyncing flag.
   *
   * @param bool $status
   *   The status of the sync flag.
   */
  public function setSyncing($status);

  /**
   * Returns whether the configuration entity is enabled.
   *
   * Status implementations for configuration entities should follow these
   * general rules:
   *   - Status does not affect the loading of entities. I.e. Disabling
   *     configuration entities should only have UI/access implications.
   *   - It should only take effect when a 'status' key is explicitly declared
   *     in the entity_keys info of a configuration entitys annotation data.
   *   - Each entity implementation (entity/controller) is responsible for
   *     checking and managing the status.
   *
   * @return bool
   */
  public function status();

  /**
   * Returns whether the configuration entity is created, updated or deleted
   * through the import process.
   *
   * @return bool
   */
  public function isSyncing();

  /**
   * Returns the value of a property.
   *
   * @param string $property_name
   *   The name of the property that should be returned.
   *
   * @return mixed
   *   The property, if existing, NULL otherwise.
   */
  public function get($property_name);

  /**
   * Sets the value of a property.
   *
   * @param string $property_name
   *   The name of the property that should be set.
   * @param mixed $value
   *   The value the property should be set to.
   */
  public function set($property_name, $value);

  /**
   * Retrieves the exportable properties of the entity.
   *
   * These are the values that get saved into config.
   *
   * @return array
   *   An array of exportable properties and their values.
   */
  public function getExportProperties();

}
