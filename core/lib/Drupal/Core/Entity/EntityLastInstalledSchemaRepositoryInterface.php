<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Provides an interface for an installed entity definition repository.
 */
interface EntityLastInstalledSchemaRepositoryInterface {

  /**
   * Gets the entity type definition in its most recently installed state.
   *
   * During the application lifetime, entity type definitions can change. For
   * example, updated code can be deployed. The getDefinition() method will
   * always return the definition as determined by the current codebase. This
   * method, however, returns what the definition was when the last time that
   * one of the \Drupal\Core\Entity\EntityTypeListenerInterface events was last
   * fired and completed successfully. In other words, the definition that
   * the entity type's handlers have incorporated into the application state.
   * For example, if the entity type's storage handler is SQL-based, the
   * definition for which database tables were created.
   *
   * Application management code can check if getDefinition() differs from
   * getLastInstalledDefinition() and decide whether to:
   * - Invoke the appropriate \Drupal\Core\Entity\EntityTypeListenerInterface
   *   event so that handlers react to the new definition.
   * - Raise a warning that the application state is incompatible with the
   *   codebase.
   * - Perform some other action.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   *   The installed entity type definition, or NULL if the entity type has
   *   not yet been installed via onEntityTypeCreate().
   *
   * @see \Drupal\Core\Entity\EntityTypeListenerInterface
   */
  public function getLastInstalledDefinition($entity_type_id);

  /**
   * Stores the entity type definition in the application state.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   *
   * @return $this
   */
  public function setLastInstalledDefinition(EntityTypeInterface $entity_type);

  /**
   * Deletes the entity type definition from the application state.
   *
   * @param string $entity_type_id
   *   The entity type definition identifier.
   *
   * @return $this
   */
  public function deleteLastInstalledDefinition($entity_type_id);

  /**
   * Gets the entity type's most recently installed field storage definitions.
   *
   * During the application lifetime, field storage definitions can change. For
   * example, updated code can be deployed. The getFieldStorageDefinitions()
   * method will always return the definitions as determined by the current
   * codebase. This method, however, returns what the definitions were when the
   * last time that one of the
   * \Drupal\Core\Field\FieldStorageDefinitionListenerInterface events was last
   * fired and completed successfully. In other words, the definitions that
   * the entity type's handlers have incorporated into the application state.
   * For example, if the entity type's storage handler is SQL-based, the
   * definitions for which database tables were created.
   *
   * Application management code can check if getFieldStorageDefinitions()
   * differs from getLastInstalledFieldStorageDefinitions() and decide whether
   * to:
   * - Invoke the appropriate
   *   \Drupal\Core\Field\FieldStorageDefinitionListenerInterface
   *   events so that handlers react to the new definitions.
   * - Raise a warning that the application state is incompatible with the
   *   codebase.
   * - Perform some other action.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface[]
   *   The array of installed field storage definitions for the entity type,
   *   keyed by field name.
   *
   * @see \Drupal\Core\Entity\EntityTypeListenerInterface
   */
  public function getLastInstalledFieldStorageDefinitions($entity_type_id);

  /**
   * Stores the entity type's field storage definitions in the application state.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $storage_definitions
   *   An array of field storage definitions.
   */
  public function setLastInstalledFieldStorageDefinitions($entity_type_id, array $storage_definitions);

  /**
   * Stores the field storage definition in the application state.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   */
  public function setLastInstalledFieldStorageDefinition(FieldStorageDefinitionInterface $storage_definition);

  /**
   * Deletes the field storage definition from the application state.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   */
  public function deleteLastInstalledFieldStorageDefinition(FieldStorageDefinitionInterface $storage_definition);

}
