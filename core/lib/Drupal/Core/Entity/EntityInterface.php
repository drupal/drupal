<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Access\AccessibleInterface;

/**
 * Defines a common interface for all entity objects.
 */
interface EntityInterface extends AccessibleInterface {

  /**
   * Returns the entity UUID (Universally Unique Identifier).
   *
   * The UUID is guaranteed to be unique and can be used to identify an entity
   * across multiple systems.
   *
   * @return string
   *   The UUID of the entity, or NULL if the entity does not have one.
   */
  public function uuid();

  /**
   * Returns the identifier.
   *
   * @return string|int|null
   *   The entity identifier, or NULL if the object does not yet have an
   *   identifier.
   */
  public function id();

  /**
   * Returns the language of the entity.
   *
   * @return \Drupal\Core\Language\Language
   *   The language object.
   */
  public function language();

  /**
   * Returns whether the entity is new.
   *
   * Usually an entity is new if no ID exists for it yet. However, entities may
   * be enforced to be new with existing IDs too.
   *
   * @return
   *   TRUE if the entity is new, or FALSE if the entity has already been saved.
   *
   * @see \Drupal\Core\Entity\EntityInterface::enforceIsNew()
   */
  public function isNew();

  /**
   * Enforces an entity to be new.
   *
   * Allows migrations to create entities with pre-defined IDs by forcing the
   * entity to be new before saving.
   *
   * @param bool $value
   *   (optional) Whether the entity should be forced to be new. Defaults to
   *   TRUE.
   *
   * @see \Drupal\Core\Entity\EntityInterface::isNew()
   */
  public function enforceIsNew($value = TRUE);

  /**
   * Returns the type of the entity.
   *
   * @return
   *   The type of the entity.
   */
  public function entityType();

  /**
   * Returns the bundle of the entity.
   *
   * @return
   *   The bundle of the entity. Defaults to the entity type if the entity type
   *   does not make use of different bundles.
   */
  public function bundle();

  /**
   * Returns the label of the entity.
   *
   * @param $langcode
   *   (optional) The language code of the language that should be used for
   *   getting the label. If set to NULL, the entity's default language is
   *   used.
   *
   * @return
   *   The label of the entity, or NULL if there is no label defined.
   */
  public function label($langcode = NULL);

  /**
   * Returns the URI elements of the entity.
   *
   * @return
   *   An array containing the 'path' and 'options' keys used to build the URI
   *   of the entity, and matching the signature of url().
   */
  public function uri();

  /**
   * Returns a list of URI relationships supported by this entity.
   *
   * @return array
   *   An array of link relationships supported by this entity.
   */
  public function uriRelationships();

  /**
   * Saves an entity permanently.
   *
   * When saving existing entities, the entity is assumed to be complete,
   * partial updates of entities are not supported.
   *
   * @return
   *   Either SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function save();

  /**
   * Deletes an entity permanently.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function delete();

  /**
   * Acts on an entity before the presave hook is invoked.
   *
   * Used before the entity is saved and before invoking the presave hook.
   *
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage_controller
   *   The entity storage controller object.
   */
  public function preSave(EntityStorageControllerInterface $storage_controller);

  /**
   * Acts on a saved entity before the insert or update hook is invoked.
   *
   * Used after the entity is saved, but before invoking the insert or update
   * hook.
   *
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage_controller
   *   The entity storage controller object.
   * @param bool $update
   *   TRUE if the entity has been updated, or FALSE if it has been inserted.
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE);

  /**
   * Changes the values of an entity before it is created.
   *
   * Load defaults for example.
   *
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage_controller
   *   The entity storage controller object.
   * @param array $values
   *   An array of values to set, keyed by property name. If the entity type has
   *   bundles the bundle key has to be specified.
   */
  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values);

  /**
   * Acts on an entity after it is created but before hooks are invoked.
   *
   * @param EntityStorageControllerInterface $storage_controller
   *   The entity storage controller object.
   */
  public function postCreate(EntityStorageControllerInterface $storage_controller);

  /**
   * Acts on entities before they are deleted and before hooks are invoked.
   *
   * Used before the entities are deleted and before invoking the delete hook.
   *
   * @param EntityStorageControllerInterface $storage_controller
   *   The entity storage controller object.
   * @param array $entities
   *   An array of entities.
   */
  public static function preDelete(EntityStorageControllerInterface $storage_controller, array $entities);

  /**
   * Acts on deleted entities before the delete hook is invoked.
   *
   * Used after the entities are deleted but before invoking the delete hook.
   *
   * @param EntityStorageControllerInterface $storage_controller
   *   The entity storage controller object.
   * @param array $entities
   *   An array of entities.
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities);

  /**
   * Acts on loaded entities before the load hook is invoked.
   *
   * @param EntityStorageControllerInterface $storage_controller
   *   The entity storage controller object.
   * @param array $entities
   *   An array of entities.
   */
  public static function postLoad(EntityStorageControllerInterface $storage_controller, array $entities);

  /**
   * Creates a duplicate of the entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A clone of the current entity with all identifiers unset, so saving
   *   it inserts a new entity into the storage system.
   */
  public function createDuplicate();

  /**
   * Returns the info of the type of the entity.
   *
   * @see entity_get_info()
   */
  public function entityInfo();

  /**
   * Returns a list of entities referenced by this entity.
   *
   * @return array
   *   An array of entities.
   */
  public function referencedEntities();

  /**
   * Acts on an entity after it was saved or deleted.
   */
  public function changed();

}
