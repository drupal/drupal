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
   * Enables the configuration entity.
   *
   * @return $this
   */
  public function enable();

  /**
   * Disables the configuration entity.
   *
   * @return $this
   */
  public function disable();

  /**
   * Sets the status of the configuration entity.
   *
   * @param bool $status
   *   The status of the configuration entity.
   *
   * @return $this
   */
  public function setStatus($status);

  /**
   * Sets the status of the isSyncing flag.
   *
   * @param bool $status
   *   The status of the sync flag.
   *
   * @return $this
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
   * Returns whether this entity is being changed during the uninstall process.
   *
   * If you are writing code that responds to a change in this entity (insert,
   * update, delete, presave, etc.), and your code would result in a
   * configuration change (whether related to this configuration entity, another
   * configuration entity, or non-entity configuration) or your code would
   * result in a change to this entity itself, you need to check and see if this
   * entity change is part of an uninstall process, and skip executing your code
   * if that is the case.
   *
   * For example, \Drupal\language\Entity\Language::preDelete() prevents the API
   * from deleting the default language. However during an uninstall of the
   * language module it is expected that the default language should be deleted.
   *
   * @return bool
   */
  public function isUninstalling();

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
   *
   * @return $this
   */
  public function set($property_name, $value);

  /**
   * Calculates dependencies and stores them in the dependency property.
   *
   * @return array
   *   An array of dependencies grouped by type (module, theme, entity).
   */
  public function calculateDependencies();

  /**
   * Gets the configuration dependency name.
   *
   * @return string
   *   The configuration dependency name.
   */
  public function getConfigDependencyName();

}
