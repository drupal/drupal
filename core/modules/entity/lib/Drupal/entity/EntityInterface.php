<?php

/**
 * @file
 * Definition of Drupal\entity\EntityInterface.
 */

namespace Drupal\entity;

/**
 * Defines a common interface for all entity objects.
 */
interface EntityInterface {

  /**
   * Constructs a new entity object.
   *
   * @param $values
   *   An array of values to set, keyed by property name. If the entity type
   *   has bundles, the bundle key has to be specified.
   * @param $entity_type
   *   The type of the entity to create.
   */
  public function __construct(array $values, $entity_type);

  /**
   * Returns the entity identifier (the entity's machine name or numeric ID).
   *
   * @return
   *   The identifier of the entity, or NULL if the entity does not yet have
   *   an identifier.
   */
  public function id();

  /**
   * Returns whether the entity is new.
   *
   * Usually an entity is new if no ID exists for it yet. However, entities may
   * be enforced to be new with existing IDs too.
   *
   * @return
   *   TRUE if the entity is new, or FALSE if the entity has already been saved.
   *
   * @see Drupal\entity\EntityInterface::enforceIsNew()
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
   * @see Drupal\entity\EntityInterface::isNew()
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
   * @return
   *   The label of the entity, or NULL if there is no label defined.
   */
  public function label();

  /**
   * Returns the URI elements of the entity.
   *
   * @return
   *   An array containing the 'path' and 'options' keys used to build the URI
   *   of the entity, and matching the signature of url(). NULL if the entity
   *   has no URI of its own.
   */
  public function uri();

  /**
   * Returns the default language of a language-specific entity.
   *
   * @return
   *   The language object of the entity's default language, or FALSE if the
   *   entity is not language-specific.
   *
   * @see Drupal\entity\EntityInterface::translations()
   */
  public function language();

  /**
   * Returns the languages the entity is translated to.
   *
   * @return
   *   An array of language objects, keyed by language codes.
   *
   * @see Drupal\entity\EntityInterface::language()
   */
  public function translations();

  /**
   * Returns the value of an entity property.
   *
   * @param $property_name
   *   The name of the property to return; e.g., 'title'.
   * @param $langcode
   *   (optional) If the property is translatable, the language code of the
   *   language that should be used for getting the property. If set to NULL,
   *   the entity's default language is being used.
   *
   * @return
   *   The property value, or NULL if it is not defined.
   *
   * @see Drupal\entity\EntityInterface::language()
   */
  public function get($property_name, $langcode = NULL);

  /**
   * Sets the value of an entity property.
   *
   * @param $property_name
   *   The name of the property to set; e.g., 'title'.
   * @param $value
   *   The value to set, or NULL to unset the property.
   * @param $langcode
   *   (optional) If the property is translatable, the language code of the
   *   language that should be used for getting the property. If set to
   *   NULL, the entity's default language is being used.
   *
   * @see Drupal\entity\EntityInterface::language()
   */
  public function set($property_name, $value, $langcode = NULL);

  /**
   * Saves an entity permanently.
   *
   * @return
   *   Either SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   *
   * @throws Drupal\entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function save();

  /**
   * Deletes an entity permanently.
   *
   * @throws Drupal\entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function delete();

  /**
   * Creates a duplicate of the entity.
   *
   * @return Drupal\entity\EntityInterface
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
}
