<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\TypedData\AccessibleInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\IdentifiableInterface;
use Drupal\Core\TypedData\TranslatableInterface;

/**
 * Defines a common interface for all entity objects.
 *
 * This interface builds upon the general interfaces provided by the typed data
 * API, while extending them with entity-specific additions. I.e., an entity
 * implements the ComplexDataInterface among others, thus is complex data
 * containing fields as its data properties. The contained fields have to
 * implement the \Drupal\Core\Entity\Field\FieldInterface, which builds upon
 * typed data interfaces as well.
 *
 * When implementing this interface which extends Traversable, make sure to list
 * IteratorAggregate or Iterator before this interface in the implements clause.
 *
 * @see \Drupal\Core\TypedData\TypedDataManager
 * @see \Drupal\Core\Field\FieldInterface
 */
interface EntityInterface extends IdentifiableInterface, ComplexDataInterface, AccessibleInterface, TranslatableInterface {

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
   * Returns whether a new revision should be created on save.
   *
   * @return bool
   *   TRUE if a new revision should be created.
   *
   * @see \Drupal\Core\Entity\EntityInterface::setNewRevision()
   */
  public function isNewRevision();

  /**
   * Enforces an entity to be saved as a new revision.
   *
   * @param bool $value
   *   (optional) Whether a new revision should be saved.
   *
   * @see \Drupal\Core\Entity\EntityInterface::isNewRevision()
   */
  public function setNewRevision($value = TRUE);

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
   * Acts on a revision before it gets saved.
   *
   * @param EntityStorageControllerInterface $storage_controller
   *   The entity storage controller object.
   * @param \stdClass $record
   *   The revision object.
   */
  public function preSaveRevision(EntityStorageControllerInterface $storage_controller, \stdClass $record);

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
   * Returns the revision identifier of the entity.
   *
   * @return
   *   The revision identifier of the entity, or NULL if the entity does not
   *   have a revision identifier.
   */
  public function getRevisionId();

  /**
   * Checks if this entity is the default revision.
   *
   * @param bool $new_value
   *   (optional) A Boolean to (re)set the isDefaultRevision flag.
   *
   * @return bool
   *   TRUE if the entity is the default revision, FALSE otherwise. If
   *   $new_value was passed, the previous value is returned.
   */
  public function isDefaultRevision($new_value = NULL);

  /**
   * Retrieves the exportable properties of the entity.
   *
   * @return array
   *   An array of exportable properties and their values.
   */
  public function getExportProperties();

  /**
   * Returns the translation support status.
   *
   * @return bool
   *   TRUE if the entity bundle has translation support enabled.
   */
  public function isTranslatable();

  /**
   * Marks the translation identified by the given language code as existing.
   *
   * @todo Remove this as soon as translation metadata have been converted to
   *    regular fields.
   *
   * @param string $langcode
   *   The language code identifying the translation to be initialized.
   */
  public function initTranslation($langcode);

  /**
   * Defines the base fields of the entity type.
   *
   * @param string $entity_type
   *   The entity type to return properties for. Useful when a single class is
   *   used for multiple, possibly dynamic entity types.
   *
   * @return array
   *   An array of entity field definitions as specified by
   *   \Drupal\Core\Entity\EntityManager::getFieldDefinitions(), keyed by field
   *   name.
   *
   * @see \Drupal\Core\Entity\EntityManager::getFieldDefinitions()
   */
  public static function baseFieldDefinitions($entity_type);

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
