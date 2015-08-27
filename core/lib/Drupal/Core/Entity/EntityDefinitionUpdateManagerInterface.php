<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

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
 * This interface also provides methods to retrieve instances of the definitions
 * to be updated ready to be manipulated. In fact when definitions change in
 * code the system needs to be notified about that and the definitions stored in
 * state need to be reconciled with the ones living in code. This typically
 * happens in Update API functions, which need to take the system from a known
 * state to another known state. Relying on the definitions living in code might
 * prevent this, as the system might transition directly to the last available
 * state, and thus skipping the intermediate steps. Manipulating the definitions
 * in state allows to avoid this and ensures that the various steps of the
 * update process are predictable and repeatable.
 *
 * @see \Drupal\Core\Entity\EntityManagerInterface::getDefinition()
 * @see \Drupal\Core\Entity\EntityManagerInterface::getLastInstalledDefinition()
 * @see \Drupal\Core\Entity\EntityManagerInterface::getFieldStorageDefinitions()
 * @see \Drupal\Core\Entity\EntityManagerInterface::getLastInstalledFieldStorageDefinitions()
 * @see hook_update_N()
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
   * Use this with care, as it will apply updates for any module, which will
   * lead to unpredictable results.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   This exception is thrown if a change cannot be applied without
   *   unacceptable data loss. In such a case, the site administrator needs to
   *   apply some other process, such as a custom update function or a
   *   migration via the Migrate module.
   */
  public function applyUpdates();

  /**
   * Returns an entity type definition ready to be manipulated.
   *
   * When needing to apply updates to existing entity type definitions, this
   * method should always be used to retrieve a definition ready to be
   * manipulated.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The entity type definition.
   */
  public function getEntityType($entity_type_id);

  /**
   * Installs a new entity type definition.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   */
  public function installEntityType(EntityTypeInterface $entity_type);

  /**
   * Applies any change performed to the passed entity type definition.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   */
  public function updateEntityType(EntityTypeInterface $entity_type);

  /**
   * Uninstalls an entity type definition.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   */
  public function uninstallEntityType(EntityTypeInterface $entity_type);

  /**
   * Returns a field storage definition ready to be manipulated.
   *
   * When needing to apply updates to existing field storage definitions, this
   * method should always be used to retrieve a storage definition ready to be
   * manipulated.
   *
   * @param string $name
   *   The field name.
   * @param string $entity_type_id
   *   The entity type identifier.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface
   *   The field storage definition.
   *
   * @todo Make this return a mutable storage definition interface when we have
   *   one. See https://www.drupal.org/node/2346329.
   */
  public function getFieldStorageDefinition($name, $entity_type_id);

  /**
   * Installs a new field storage definition.
   *
   * @param string $name
   *   The field storage definition name.
   * @param string $entity_type_id
   *   The target entity type identifier.
   * @param string $provider
   *   The name of the definition provider.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   */
  public function installFieldStorageDefinition($name, $entity_type_id, $provider, FieldStorageDefinitionInterface $storage_definition);

  /**
   * Applies any change performed to the passed field storage definition.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   */
  public function updateFieldStorageDefinition(FieldStorageDefinitionInterface $storage_definition);

  /**
   * Uninstalls a field storage definition.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   */
  public function uninstallFieldStorageDefinition(FieldStorageDefinitionInterface $storage_definition);

}
