<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Defines a common interface for all entity objects.
 *
 * @ingroup entity_api
 */
interface EntityInterface extends AccessibleInterface, CacheableDependencyInterface {

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
   * @return \Drupal\Core\Language\LanguageInterface
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
   * @return $this
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
   *   "canonical" = "/node/{node}",
   *   "edit-form" = "/node/{node}/edit",
   *   "version-history" = "/node/{node}/revisions"
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
   * @param array $options
   *   See \Drupal\Core\Routing\UrlGeneratorInterface::generateFromRoute() for
   *   the available options.
   *
   * @return \Drupal\Core\Url
   */
  public function urlInfo($rel = 'canonical', array $options = array());

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
   * Generates the HTML for a link to this entity.
   *
   * @param string|null $text
   *   (optional) The link text for the anchor tag as a translated string.
   *   If NULL, it will use the entity's label. Defaults to NULL.
   * @param string $rel
   *   (optional) The link relationship type. Defaults to 'canonical'.
   * @param array $options
   *   See \Drupal\Core\Routing\UrlGeneratorInterface::generateFromRoute() for
   *   the available options.
   *
   * @return string
   *   An HTML string containing a link to the entity.
   */
  public function link($text = NULL, $rel = 'canonical', array $options = []);

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
   *
   * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.0. Use
   *    static::urlInfo() instead.
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
   * Loads an entity.
   *
   * @param mixed $id
   *   The id of the entity to load.
   *
   * @return static
   *   The entity object or NULL if there is no entity with the given ID.
   */
  public static function load($id);

  /**
   * Loads one or more entities.
   *
   * @param array $ids
   *   An array of entity IDs, or NULL to load all entities.
   *
   * @return static[]
   *   An array of entity objects indexed by their IDs.
   */
  public static function loadMultiple(array $ids = NULL);

  /**
   * Constructs a new entity object, without permanently saving it.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name. If the
   *   entity type has bundles, the bundle key has to be specified.
   *
   * @return static
   *   The entity object.
   */
  public static function create(array $values = array());

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
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   */
  public function preSave(EntityStorageInterface $storage);

  /**
   * Acts on a saved entity before the insert or update hook is invoked.
   *
   * Used after the entity is saved, but before invoking the insert or update
   * hook.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   * @param bool $update
   *   TRUE if the entity has been updated, or FALSE if it has been inserted.
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE);

  /**
   * Changes the values of an entity before it is created.
   *
   * Load defaults for example.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   * @param mixed[] $values
   *   An array of values to set, keyed by property name. If the entity type has
   *   bundles the bundle key has to be specified.
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values);

  /**
   * Acts on an entity after it is created but before hooks are invoked.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   */
  public function postCreate(EntityStorageInterface $storage);

  /**
   * Acts on entities before they are deleted and before hooks are invoked.
   *
   * Used before the entities are deleted and before invoking the delete hook.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entities.
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities);

  /**
   * Acts on deleted entities before the delete hook is invoked.
   *
   * Used after the entities are deleted but before invoking the delete hook.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entities.
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities);

  /**
   * Acts on loaded entities.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entities.
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities);

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
   *   The entity type definition.
   */
  public function getEntityType();

  /**
   * Returns a list of entities referenced by this entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entities.
   */
  public function referencedEntities();

  /**
   * Returns the original ID.
   *
   * @return int|string|null
   *   The original ID, or NULL if no ID was set or for entity types that do not
   *   support renames.
   */
  public function getOriginalId();

  /**
   * Sets the original ID.
   *
   * @param int|string|null $id
   *   The new ID to set as original ID. If the entity supports renames, setting
   *   NULL will prevent an update from being considered a rename.
   *
   * @return $this
   */
  public function setOriginalId($id);

  /**
   * Returns an array of all property values.
   *
   * @return mixed[]
   *   An array of property values, keyed by property name.
   */
  public function toArray();

  /**
   * Returns a typed data object for this entity object.
   *
   * The returned typed data object wraps this entity and allows dealing with
   * entities based on the generic typed data API.
   *
   * @return \Drupal\Core\TypedData\ComplexDataInterface
   *   The typed data object for this entity.
   *
   * @see \Drupal\Core\TypedData\TypedDataInterface
   */
  public function getTypedData();

  /**
   * Gets the key that is used to store configuration dependencies.
   *
   * @return string
   *   The key to be used in configuration dependencies when storing
   *   dependencies on entities of this type.
   *
   * @see \Drupal\Core\Entity\EntityTypeInterface::getConfigDependencyKey()
   */
  public function getConfigDependencyKey();

  /**
   * Gets the configuration dependency name.
   *
   * Configuration entities can depend on content and configuration entities.
   * They store an array of content and config dependency names in their
   * "dependencies" key.
   *
   * @return string
   *   The configuration dependency name.
   *
   * @see \Drupal\Core\Config\Entity\ConfigDependencyManager
   */
  public function getConfigDependencyName();

  /**
   * Gets the configuration target identifier for the entity.
   *
   * Used to supply the correct format for storing a reference targeting this
   * entity in configuration.
   *
   * @return string
   *   The configuration target identifier.
   */
  public function getConfigTarget();

}
