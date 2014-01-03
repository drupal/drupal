<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityTypeInterface.
 */

namespace Drupal\Core\Entity;

/**
 * Provides an interface for an entity type and its metadata.
 *
 * Additional information can be provided by modules: hook_entity_info() can be
 * implemented to define new properties, while hook_entity_info_alter() can be
 * implemented to alter existing data and fill-in defaults. Module-specific
 * properties should be documented in the hook implementations defining them.
 */
interface EntityTypeInterface {

  /**
   * Gets any arbitrary property.
   *
   * @param string $property
   *   The property to retrieve.
   *
   * @return mixed
   *   The value for that property, or NULL if the property does not exist.
   */
  public function get($property);

  /**
   * Sets a value to an arbitrary property.
   *
   * @param string $property
   *   The property to use for the value.
   * @param mixed $value
   *   The value to set.
   *
   * @return static
   */
  public function set($property, $value);

  /**
   * Returns the unique identifier of the entity type.
   *
   * @return string
   *   The unique identifier of the entity type.
   */
  public function id();

  /**
   * Returns the name of the provider of this entity type.
   *
   * @return string
   *   The name of the provider of this entity type.
   */
  public function getProvider();

  /**
   * Returns the name of the entity type class.
   *
   * @return string
   *   The name of the entity type class.
   */
  public function getClass();

  /**
   * Returns an array of entity keys.
   *
   * @return array
   *   An array describing how the Field API can extract certain information
   *   from objects of this entity type:
   *   - id: The name of the property that contains the primary ID of the
   *     entity. Every entity object passed to the Field API must have this
   *     property and its value must be numeric.
   *   - revision: (optional) The name of the property that contains the
   *     revision ID of the entity. The Field API assumes that all revision IDs
   *     are unique across all entities of a type. This entry can be omitted if
   *     the entities of this type are not versionable.
   *   - bundle: (optional) The name of the property that contains the bundle
   *     name for the entity. The bundle name defines which set of fields are
   *     attached to the entity (e.g. what nodes call "content type"). This
   *     entry can be omitted if this entity type exposes a single bundle (such
   *     that all entities have the same collection of fields). The name of this
   *     single bundle will be the same as the entity type.
   *   - label: The name of the property that contains the entity label. For
   *     example, if the entity's label is located in $entity->subject, then
   *     'subject' should be specified here. If complex logic is required to
   *     build the label, a 'label_callback' should be defined instead (see the
   *     $label_callback block above for details).
   *   - uuid (optional): The name of the property that contains the universally
   *     unique identifier of the entity, which is used to distinctly identify
   *     an entity across different systems.
   */
  public function getKeys();

  /**
   * Returns a specific entity key.
   *
   * @param string $key
   *   The name of the entity key to return.
   *
   * @return string|bool
   *   The entity key, or FALSE if it does not exist.
   *
   * @see self::getKeys()
   */
  public function getKey($key);

  /**
   * Indicates if a given entity key exists.
   *
   * @param string $key
   *   The name of the entity key to check.
   *
   * @return bool
   *   TRUE if a given entity key exists, FALSE otherwise.
   */
  public function hasKey($key);

  /**
   * Indicates whether entities should be statically cached.
   *
   * @return bool
   *   TRUE if static caching should be used; FALSE otherwise.
   */
  public function isStaticallyCacheable();

  /**
   * Indicates whether the rendered output of entities should be cached.
   *
   * @return bool
   */
  public function isRenderCacheable();

  /**
   * Indicates if the persistent cache of field data should be used.
   *
   * @todo Used by FieldableEntityStorageControllerBase only.
   *
   * The persistent cache should usually only be disabled if a higher level
   * persistent cache is available for the entity type.
   *
   * @return bool
   */
  public function isFieldDataCacheable();

  /**
   * Sets the name of the entity type class.
   *
   * @param string $class
   *   The name of the entity type class.
   *
   * @return static
   */
  public function setClass($class);

  /**
   * Determines if there is a controller for a given type.
   *
   * @param string $controller_type
   *   The type of controller to check.
   *
   * @return bool
   *   TRUE if a controller of this type exists, FALSE otherwise.
   */
  public function hasController($controller_type);

  /**
   * @param string $controller_type
   *   The controller type to get.
   *
   * @return array|string
   *   The controllers for a given type.
   */
  public function getController($controller_type);

  /**
   * Returns an array of controllers.
   *
   * @return array
   *   An associative array where the keys are the names of different controller
   *   types (listed below) and the values are the names of the classes that
   *   implement that controller:
   *   - storage: The name of the class used to load the objects. The class must
   *     implement \Drupal\Core\Entity\EntityStorageControllerInterface.
   *   - form: An associative array where the keys are the names of the
   *     different form operations (such as 'create', 'edit', or 'delete') and
   *     the values are the names of the controller classes for those
   *     operations. The name of the operation is passed also to the form
   *     controller's constructor, so that one class can be used for multiple
   *     entity forms when the forms are similar. The classes must implement
   *     \Drupal\Core\Entity\EntityFormControllerInterface.
   *   - list: The name of the class that provides listings of the entities. The
   *     class must implement \Drupal\Core\Entity\EntityListControllerInterface.
   *   - render: The name of the class that is used to render the entities. The
   *     class must implement \Drupal\Core\Entity\EntityViewBuilderInterface.
   *   - access: The name of the class that is used for access checks. The class
   *     must implement \Drupal\Core\Entity\EntityAccessControllerInterface.
   *     Defaults to \Drupal\Core\Entity\EntityAccessController.
   */
  public function getControllers();

  /**
   * Sets a form class for a specific operation.
   *
   * @param string $operation
   *   The operation to use this form class for.
   * @param string $class
   *   The form class to use for the operation.
   *
   * @return static
   */
  public function setForm($operation, $class);

  /**
   * Sets the listing class.
   *
   * @param string $class
   *   The list class to use for the operation.
   *
   * @return static
   */
  public function setList($class);

  /**
   * Indicates if the entity type is a subclass of the given class or interface.
   *
   * @param string $class
   *   The class or interface to check.
   *
   * @return bool
   *   TRUE if the entity type is a subclass of the class or interface.
   */
  public function isSubclassOf($class);

  /**
   * Sets the controllers for a given type.
   *
   * @param string $controller_type
   *   The type of controller to set.
   * @param array|string $value
   *   The value for a controller type.
   *
   * @return static
   */
  public function setController($controller_type, $value);

  /**
   * Returns the name of the default administrative permission.
   *
   * The default \Drupal\Core\Entity\EntityAccessController class checks this
   * permission for all operations in its checkAccess() method. Entities with
   * more complex permissions can extend this class to do their own access
   * checks.
   *
   * @return string|bool
   */
  public function getAdminPermission();

  /**
   * Returns the permission granularity level.
   *
   * The allowed values are respectively "entity_type", "bundle" or FALSE.
   *
   * @return string|bool
   *   Whether a module exposing permissions for the current entity type
   *   should use entity-type level granularity, bundle level granularity or
   *   just skip this entity.
   */
  public function getPermissionGranularity();

  /**
   * Get all bundle keys defined on the annotation.
   *
   * @return array
   *   An array describing how the Field API can extract the information it
   *   needs from the bundle objects for this type (e.g Vocabulary objects for
   *   terms; not applicable for nodes):
   *   - bundle: The name of the property that contains the name of the bundle
   *     object.
   */
  public function getBundleKeys();

  /**
   * Returns a single bundle key.
   *
   * @param string $name
   *   The name of the bundle key.
   *
   * @return string|bool
   *   The value of the bundle key.
   */
  public function getBundleKey($name);

  /**
   * Indicates whether fields can be attached to entities of this type.
   *
   * @return bool
   *   Returns TRUE if the entity type can has fields, otherwise FALSE.
   */
  public function isFieldable();

  /**
   * Returns link templates using the URI template syntax.
   *
   * Links are an array of standard link relations to the URI template that
   * should be used for them. Where possible, link relationships should use
   * established IANA relationships rather than custom relationships.
   *
   * Every entity type should, at minimum, define "canonical", which is the
   * pattern for URIs to that entity. Even if the entity will have no HTML page
   * exposed to users it should still have a canonical URI in order to be
   * compatible with web services. Entities that will be user-editable via an
   * HTML page must also define an "edit-form" relationship.
   *
   * By default, the following placeholders are supported:
   * - entityType: The machine name of the entity type.
   * - bundle: The bundle machine name of the entity.
   * - id: The unique ID of the entity.
   * - uuid: The UUID of the entity.
   * - [entityType]: The entity type itself will also be a valid token for the
   *   ID of the entity. For instance, a placeholder of {node} used on the Node
   *   class would have the same value as {id}. This is generally preferred
   *   over "id" for better self-documentation.
   *
   * Specific entity types may also expand upon this list by overriding the
   * Entity::uriPlaceholderReplacements() method.
   *
   * @link http://www.iana.org/assignments/link-relations/link-relations.xml @endlink
   * @link http://tools.ietf.org/html/rfc6570 @endlink
   *
   * @return array
   */
  public function getLinkTemplates();

  /**
   * Returns the link template for a given key.
   *
   * @param string $key
   *   The link type.
   *
   * @return string|bool
   *   The route name for this link, or FALSE if it doesn't exist.
   */
  public function getLinkTemplate($key);

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
   * Sets a single link template.
   *
   * @param string $key
   *   The name of a link.
   * @param string $route_name
   *   The route name to use for the link.
   *
   * @return static
   */
  public function setLinkTemplate($key, $route_name);

  /**
   * Gets the callback for the label of the entity.
   *
   * The function takes an entity and returns the label of the entity. Use
   * language() on the entity to get information on the requested language. The
   * entity label is the main string associated with an entity; for example, the
   * title of a node or the subject of a comment. If there is an entity object
   * property that defines the label, use the 'label' element of the
   * 'entity_keys' return value component to provide this information (see
   * below). If more complex logic is needed to determine the label of an
   * entity, you can instead specify a callback function here, which will be
   * called to determine the entity label. See also the
   * \Drupal\Core\Entity\EntityInterface::label() method, which implements this
   * logic.
   *
   * @return callable|bool
   *   The callback, or FALSE if none exists.
   */
  public function getLabelCallback();

  /**
   * Sets the label callback.
   *
   * @param callable $callback
   *   A callable that returns the label of the entity.
   *
   * @return static
   */
  public function setLabelCallback($callback);

  /**
   * Indicates if a label callback exists.
   *
   * @return bool
   */
  public function hasLabelCallback();

  /**
   * Returns the name of the entity type which provides bundles.
   *
   * @return string
   */
  public function getBundleEntityType();

  /**
   * Returns the entity type for which this entity provides bundles.
   *
   * It can be used by other modules to act accordingly; for example,
   * the Field UI module uses it to add operation links to manage fields and
   * displays.
   *
   * @return string|bool
   *   The entity type for which this entity provides bundles, or FALSE if does
   *   not provide bundles for another entity type.
   */
  public function getBundleOf();

  /**
   * Returns the label for the bundle.
   *
   * @return string|bool
   *   The bundle label, or FALSE if none exists.
   */
  public function getBundleLabel();

  /**
   * Returns the name of the entity's base table.
   *
   * @todo Used by DatabaseStorageController only.
   *
   * @return string|bool
   *   The name of the entity's base table, or FALSE if none exists.
   */
  public function getBaseTable();

  /**
   * Indicates whether entities of this type have multilingual support.
   *
   * At an entity level, this indicates language support and at a bundle level
   * this indicates translation support.
   *
   * @return bool
   */
  public function isTranslatable();

  /**
   * Returns the config prefix used by the configuration entity type.
   *
   * @todo Used for configuration entities only.
   *
   * @return string|bool
   *   The config prefix, or FALSE if not a configuration entity type.
   */
  public function getConfigPrefix();

  /**
   * Returns the name of the entity's revision data table.
   *
   * @todo Used by FieldableDatabaseStorageController only.
   *
   * @return string|bool
   *   The name of the entity type's revision data table.
   */
  public function getRevisionDataTable();

  /**
   * Returns the name of the entity's revision table.
   *
   * @todo Used by FieldableDatabaseStorageController only.
   *
   * @return string|bool
   *   The name of the entity type's revision table.
   */
  public function getRevisionTable();

  /**
   * Returns the name of the entity's data table.
   *
   * @todo Used by FieldableDatabaseStorageController only.
   *
   * @return string|bool
   *   The name of the entity type's data table.
   */
  public function getDataTable();

  /**
   * Returns the human-readable name of the entity type.
   *
   * @return string
   *   The human-readable name of the entity type.
   */
  public function getLabel();

  /**
   * Returns the lowercase form of the human-readable entity type name.
   *
   * @return string
   *   The lowercase form of the human-readable entity type name.
   */
  public function getLowercaseLabel();

  /**
   * Returns a callable that can be used to provide the entity URI.
   *
   * This is only called if there is no matching link template for the link
   * relationship type, and there is no bundle-specific callback provided.
   *
   * @return callable|bool
   *   A valid callback that is passed the entity or FALSE if none is specified.
   */
  public function getUriCallback();

  /**
   * Sets a callable to use to provide the entity URI.
   *
   * @param callable $callback
   *   A callback to use to provide a URI for the entity.
   *
   * @return static
   */
  public function setUriCallback($callback);

}
