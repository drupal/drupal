<?php

/**
 * @file
 * Definition of Drupal\Core\Config\Entity\ConfigEntityInterface.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the interface common for all configuration entities.
 *
 * @ingroup config_api
 * @ingroup entity_api
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
   *   Whether the entity is enabled or not.
   */
  public function status();

  /**
   * Returns whether this entity is being changed as part of an import process.
   *
   * If you are writing code that responds to a change in this entity (insert,
   * update, delete, presave, etc.), and your code would result in a
   * configuration change (whether related to this configuration entity, another
   * configuration entity, or non-entity configuration) or your code would
   * result in a change to this entity itself, you need to check and see if this
   * entity change is part of an import process, and skip executing your code if
   * that is the case.
   *
   * For example, \Drupal\node\Entity\NodeType::postSave() adds the default body
   * field to newly created node type configuration entities, which is a
   * configuration change. You would not want this code to run during an import,
   * because imported entities were already given the body field when they were
   * originally created, and the imported configuration includes all of their
   * currently-configured fields. On the other hand,
   * \Drupal\field\Entity\FieldStorageConfig::preSave() and the methods it calls
   * make sure that the storage tables are created or updated for the field
   * storage configuration entity, which is not a configuration change, and it
   * must be done whether due to an import or not. So, the first method should
   * check $entity->isSyncing() and skip executing if it returns TRUE, and the
   * second should not perform this check.
   *
   * @return bool
   *   TRUE if the configuration entity is being created, updated, or deleted
   *   through the import process.
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
   *   The property if it exists, or NULL otherwise.
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
