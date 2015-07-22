<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface.
 */

namespace Drupal\Core\Entity;

/**
 * Defines an interface for managing entity definition updates.
 *
 * During the application lifetime, the definitions of various entity types and
 * their data components (e.g., fields for fieldable entity types) can change.
 * For example, updated code can be deployed. Some entity handlers may need to
 * perform complex or long-running logic in response to the change. For
 * example, a SQL-based storage handler may need to update the database schema.
 *
 * To support this, \Drupal\Core\Entity\EntityManagerInterface has methods to
 * retrieve the last installed definitions as well as the definitions specified
 * by the current codebase. It also has create/update/delete methods to bring
 * the former up to date with the latter.
 *
 * However, it is not the responsibility of the entity manager to decide how to
 * report the differences or when to apply each update. This interface is for
 * managing that.
 *
 * @see \Drupal\Core\Entity\EntityManagerInterface::getDefinition()
 * @see \Drupal\Core\Entity\EntityManagerInterface::getLastInstalledDefinition()
 * @see \Drupal\Core\Entity\EntityManagerInterface::getFieldStorageDefinitions()
 * @see \Drupal\Core\Entity\EntityManagerInterface::getLastInstalledFieldStorageDefinitions()
 * @see \Drupal\Core\Entity\EntityTypeListenerInterface
 * @see \Drupal\Core\Field\FieldStorageDefinitionListenerInterface
 */
interface EntityDefinitionUpdateManagerInterface {

  /**
   * Indicates that a definition has just been created.
   *
   * @var int
   */
  const DEFINITION_CREATED = 1;

  /**
   * Indicates that a definition has changes.
   *
   * @var int
   */
  const DEFINITION_UPDATED = 2;

  /**
   * Indicates that a definition has just been deleted.
   *
   * @var int
   */
  const DEFINITION_DELETED = 3;

  /**
   * Checks if there are any definition updates that need to be applied.
   *
   * @return bool
   *   TRUE if updates are needed.
   */
  public function needsUpdates();

  /**
   * Gets a human readable summary of the detected changes.
   *
   * @return array
   *   An associative array keyed by entity type id. Each entry is an array of
   *   human-readable strings, each describing a change.
   */
  public function getChangeSummary();

  /**
   * Applies all the detected valid changes.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   This exception is thrown if a change cannot be applied without
   *   unacceptable data loss. In such a case, the site administrator needs to
   *   apply some other process, such as a custom update function or a
   *   migration via the Migrate module.
   */
  public function applyUpdates();

  /**
   * Performs a single entity definition update.
   *
   * This method should be used from hook_update_N() functions to process
   * entity definition updates as part of the update function. This is only
   * necessary if the hook_update_N() implementation relies on the entity
   * definition update. All remaining entity definition updates will be run
   * automatically after the hook_update_N() implementations.
   *
   * @param string $op
   *   The operation to perform, either static::DEFINITION_CREATED or
   *   static::DEFINITION_UPDATED.
   * @param string $entity_type_id
   *   The entity type to update.
   * @param bool $reset_cached_definitions
   *   (optional). Determines whether to clear the Entity Manager's cached
   *   definitions before applying the update. Defaults to TRUE. Can be used
   *   to prevent unnecessary cache invalidation when a hook_update_N() makes
   *   multiple calls to this method.
   *
   * @return bool
   *   TRUE if the entity update is processed, FALSE if not.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   This exception is thrown if a change cannot be applied without
   *   unacceptable data loss. In such a case, the site administrator needs to
   *   apply some other process, such as a custom update function or a
   *   migration via the Migrate module.
   */
  public function applyEntityUpdate($op, $entity_type_id, $reset_cached_definitions = TRUE);

  /**
   * Performs a single field storage definition update.
   *
   * This method should be used from hook_update_N() functions to process field
   * storage definition updates as part of the update function. This is only
   * necessary if the hook_update_N() implementation relies on the field storage
   * definition update. All remaining field storage definition updates will be
   * run automatically after the hook_update_N() implementations.
   *
   * @param string $op
   *   The operation to perform, possible values are static::DEFINITION_CREATED,
   *   static::DEFINITION_UPDATED or static::DEFINITION_DELETED.
   * @param string $entity_type_id
   *   The entity type to update.
   * @param string $field_name
   *   The field name to update.
   * @param bool $reset_cached_definitions
   *   (optional). Determines whether to clear the Entity Manager's cached
   *   definitions before applying the update. Defaults to TRUE. Can be used
   *   to prevent unnecessary cache invalidation when a hook_update_N() makes
   *   multiple calls to this method.

   * @return bool
   *   TRUE if the entity update is processed, FALSE if not.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   This exception is thrown if a change cannot be applied without
   *   unacceptable data loss. In such a case, the site administrator needs to
   *   apply some other process, such as a custom update function or a
   *   migration via the Migrate module.
   */
  public function applyFieldUpdate($op, $entity_type_id, $field_name, $reset_cached_definitions = TRUE);

}
