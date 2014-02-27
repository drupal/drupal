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
   * @return string|null
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
   * @return bool
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
   * Returns the ID of the type of the entity.
   *
   * @return string
   *   The entity type ID.
   */
  public function getEntityTypeId();

  /**
   * Returns the bundle of the entity.
   *
   * @return string
   *   The bundle of the entity. Defaults to the entity type ID if the entity
   *   type does not make use of different bundles.
   */
  public function bundle();

  /**
   * Returns the label of the entity.
   *
   * @return string|null
   *   The label of the entity, or NULL if there is no label defined.
   */
  public function label();

  /**
   * Returns the URI elements of the entity.
   *
   * URI templates might be set in the links array in an annotation, for
   * example:
   * @code
   * links = {
   *   "canonical" = "node.view",
   *   "edit-form" = "node.page_edit",
   *   "version-history" = "node.revision_overview"
   * }
   * @endcode
   * or specified in a callback function set like:
   * @code
   * uri_callback = "comment_uri",
   * @endcode
   * If the path is not set in the links array, the uri_callback function is
   * used for setting the path. If this does not exist and the link relationship
   * type is canonical, the path is set using the default template:
   * entity/entityType/id.
   *
   * @param string $rel
   *   The link relationship type, for example: canonical or edit-form.
   *
   * @return mixed[]
   *   An array containing 'route_name', 'route_parameters' and 'options' keys
   *   used to build the URI of the entity and matching the signature of
   *   \Drupal::url().
   */
  public function urlInfo($rel = 'canonical');

  /**
   * Returns the public URL for this entity.
   *
   * @param string $rel
   *   The link relationship type, for example: canonical or edit-form.
   * @param array $options
   *   See \Drupal\Core\Routing\UrlGeneratorInterface::generateFromRoute() for
   *   the available options.
   *
   * @return string
   *   The URL for this entity.
   */
  public function url($rel = 'canonical', $options = array());

  /**
   * Returns the internal path for this entity.
   *
   * self::url() will return the full path including any prefixes, fragments, or
   * query strings. This path does not include those.
   *
   * @param string $rel
   *   The link relationship type, for example: canonical or edit-form.
   *
   * @return string
   *   The internal path for this entity.
   */
  public function getSystemPath($rel = 'canonical');

  /**
   * Indicates if a link template exists for a given key.
   *
   * @param string $key
   *   The link type.
   *
   * @return bool
   *   TRUE if the link template exists, FALSE otherwise.
   */
  public function hasLinkTemplate($key);

  /**
   * Returns a list of URI relationships supported by this entity.
   *
   * @return string[]
   *   An array of link relationships supported by this entity.
   */
  public function uriRelationships();

  /**
   * Saves an entity permanently.
   *
   * When saving existing entities, the entity is assumed to be complete,
   * partial updates of entities are not supported.
   *
   * @return int
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
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
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
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entities.
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities);

  /**
   * Acts on loaded entities.
   *
   * @param EntityStorageControllerInterface $storage_controller
   *   The entity storage controller object.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entities.
   */
  public static function postLoad(EntityStorageControllerInterface $storage_controller, array &$entities);

  /**
   * Creates a duplicate of the entity.
   *
   * @return static
   *   A clone of $this with all identifiers unset, so saving it inserts a new
   *   entity into the storage system.
   */
  public function createDuplicate();

  /**
   * Returns the entity type definition.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   Entity type definition.
   */
  public function getEntityType();

  /**
   * Returns a list of entities referenced by this entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entities.
   */
  public function referencedEntities();

}
