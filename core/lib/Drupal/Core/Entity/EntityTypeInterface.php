<?php

namespace Drupal\Core\Entity;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;

/**
 * Provides an interface for an entity type and its metadata.
 *
 * Entity type classes can provide docblock annotations. The entity type manager
 * will use these annotations to populate the entity type object with
 * properties.
 *
 * Additional properties can be defined by module implementations of
 * hook_entity_type_build(). Existing data can be altered in implementations of
 * hook_entity_type_alter(), which can also be used to fill in defaults.
 * Module-specific properties should be documented in the hook implementations
 * defining them.
 *
 * @see \Drupal\Core\Entity\EntityTypeManagerInterface
 * @see hook_entity_type_build()
 * @see hook_entity_type_alter()
 */
interface EntityTypeInterface extends PluginDefinitionInterface {

  /**
   * The maximum length of ID, in characters.
   */
  const ID_MAX_LENGTH = 32;

  /**
   * The maximum length of bundle name, in characters.
   */
  const BUNDLE_MAX_LENGTH = 32;

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
   * @return $this
   */
  public function set($property, $value);

  /**
   * Gets the name of the original entity type class.
   *
   * In case the class name was changed with setClass(), this will return
   * the initial value. Useful when trying to identify the entity type ID based
   * on the class.
   *
   * @return string
   *   The name of the original entity type class.
   */
  public function getOriginalClass();

  /**
   * Gets an array of entity keys.
   *
   * @return array
   *   An array describing how the Field API can extract certain information
   *   from objects of this entity type:
   *   - id: The name of the property that contains the primary ID of the
   *     entity. Every entity object passed to the Field API must have this
   *     property and its value must be numeric.
   *   - revision: (optional) The name of the property that contains the
   *     revision ID of the entity. The Field API assumes that all revision IDs
   *     are unique across all entities of a type. If this entry is omitted
   *     the entities of this type are not revisionable.
   *   - bundle: (optional) The name of the property that contains the bundle
   *     name for the entity. The bundle name defines which set of fields are
   *     attached to the entity (e.g. what nodes call "content type"). This
   *     entry can be omitted if this entity type exposes a single bundle (such
   *     that all entities have the same collection of fields). The name of this
   *     single bundle will be the same as the entity type.
   *   - label: (optional) The name of the property that contains the entity
   *     label. For example, if the entity's label is located in
   *     $entity->subject, then 'subject' should be specified here. If complex
   *     logic is required to build the label,
   *     \Drupal\Core\Entity\EntityInterface::label() should be used.
   *   - langcode: (optional) The name of the property that contains the
   *     language code. For instance, if the entity's language is located in
   *     $entity->langcode, then 'langcode' should be specified here.
   *   - uuid: (optional) The name of the property that contains the universally
   *     unique identifier of the entity, which is used to distinctly identify
   *     an entity across different systems.
   */
  public function getKeys();

  /**
   * Gets a specific entity key.
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
   * @todo Used by ContentEntityStorageBase only.
   *
   * The persistent cache should usually only be disabled if a higher level
   * persistent cache is available for the entity type.
   *
   * @return bool
   */
  public function isPersistentlyCacheable();

  /**
   * Determines if there is a handler for a given type.
   *
   * @param string $handler_type
   *   The type of handler to check.
   * @param bool $nested
   *   (optional) If this handler has a nested definition. Defaults to FALSE.
   *
   * @return bool
   *   TRUE if a handler of this type exists, FALSE otherwise.
   */
  public function hasHandlerClass($handler_type, $nested = FALSE);

  /**
   * @param string $handler_type
   *   The handler type to get.
   *
   * @return array|string|null
   *   The handlers for a given type, or NULL if none exist.
   */
  public function getHandlerClass($handler_type);

  /**
   * Gets an array of handlers.
   *
   * @return array
   *   An associative array where the keys are the names of different handler
   *   types (listed below) and the values are the names of the classes that
   *   implement that handler:
   *   - storage: The name of the class used to load the objects. The class must
   *     implement \Drupal\Core\Entity\EntityStorageInterface.
   *   - form: An associative array where the keys are the names of the
   *     different form operations (such as 'create', 'edit', or 'delete') and
   *     the values are the names of the handler classes for those
   *     operations. The name of the operation is passed also to the form
   *     handler's constructor, so that one class can be used for multiple
   *     entity forms when the forms are similar. The classes must implement
   *     \Drupal\Core\Entity\EntityFormInterface.
   *   - list: The name of the class that provides listings of the entities. The
   *     class must implement \Drupal\Core\Entity\EntityListBuilderInterface.
   *   - render: The name of the class that is used to render the entities. The
   *     class must implement \Drupal\Core\Entity\EntityViewBuilderInterface.
   *   - access: The name of the class that is used for access checks. The class
   *     must implement \Drupal\Core\Entity\EntityAccessControlHandlerInterface.
   *     Defaults to \Drupal\Core\Entity\EntityAccessControlHandler.
   *   - route_provider: (optional) A list of class names, keyed by a group
   *     string, which will be used to define routes related to this entity
   *     type. These classes must implement
   *     \Drupal\Core\Entity\Routing\EntityRouteProviderInterface.
   */
  public function getHandlerClasses();

  /**
   * Gets the storage class.
   *
   * @return string
   *   The class for this entity type's storage.
   */
  public function getStorageClass();

  /**
   * Sets the storage class.
   *
   * @param string $class
   *   The class for this entity type's storage.
   *
   * @return $this
   */
  public function setStorageClass($class);

  /**
   * Gets the form class for a specific operation.
   *
   * @param string $operation
   *   The name of the operation to use, e.g., 'default'.
   *
   * @return string
   *   The class for this operation's form for this entity type.
   *
   * @see \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  public function getFormClass($operation);

  /**
   * Sets a form class for a specific operation.
   *
   * @param string $operation
   *   The operation to use this form class for.
   * @param string $class
   *   The form class implementing
   *   \Drupal\Core\Entity\EntityFormInterface.
   *
   * @return $this
   *
   * @see \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  public function setFormClass($operation, $class);

  /**
   * Indicates if this entity type has any forms.
   *
   * @return bool
   *   TRUE if there are any forms for this entity type, FALSE otherwise.
   */
  public function hasFormClasses();

  /**
   * Indicates if this entity type has any route provider.
   *
   * @return bool
   */
  public function hasRouteProviders();

  /**
   * Gets all the route provide handlers.
   *
   * Much like forms you can define multiple route provider handlers.
   *
   * @return string[]
   */
  public function getRouteProviderClasses();

  /**
   * Gets the list class.
   *
   * @return string
   *   The class for this entity type's list.
   */
  public function getListBuilderClass();

  /**
   * Sets the list class.
   *
   * @param string $class
   *   The list class to use for the operation.
   *
   * @return $this
   */
  public function setListBuilderClass($class);

  /**
   * Indicates if this entity type has a list class.
   *
   * @return bool
   *   TRUE if there is a list for this entity type, FALSE otherwise.
   */
  public function hasListBuilderClass();

  /**
   * Gets the view builder class.
   *
   * @return string
   *   The class for this entity type's view builder.
   */
  public function getViewBuilderClass();

  /**
   * Gets the view builder class.
   *
   * @param string $class
   *   The class for this entity type's view builder.
   *
   * @return $this
   */
  public function setViewBuilderClass($class);

  /**
   * Indicates if this entity type has a view builder.
   *
   * @return bool
   *   TRUE if there is a view builder for this entity type, FALSE otherwise.
   */
  public function hasViewBuilderClass();

  /**
   * Gets the access control class.
   *
   * @return string
   *   The class for this entity type's access control.
   */
  public function getAccessControlClass();

  /**
   * Sets the access control handler class.
   *
   * @param string $class
   *   The class for this entity type's access control handler.
   *
   * @return $this
   */
  public function setAccessClass($class);

  /**
   * Indicates if the entity type class implements the given interface.
   *
   * @param string $interface
   *   The class or interface to check.
   *
   * @return bool
   *   TRUE if the entity type class implements the given interface.
   */
  public function entityClassImplements($interface);

  /**
   * Indicates if the entity type is a subclass of the given class or interface.
   *
   * @param string $class
   *   The class or interface to check.
   *
   * @return bool
   *   TRUE if the entity type is a subclass of the class or interface.
   *
   * @deprecated in Drupal 8.3.0 and will be removed before Drupal 9.0.0.
   *   Use Drupal\Core\Entity\EntityTypeInterface::entityClassImplements()
   *   instead.
   */
  public function isSubclassOf($class);

  /**
   * Sets the handlers for a given type.
   *
   * @param string $handler_type
   *   The type of handler to set.
   * @param array|string $value
   *   The value for a handler type.
   *
   * @return $this
   */
  public function setHandlerClass($handler_type, $value);

  /**
   * Gets the name of the default administrative permission.
   *
   * The default \Drupal\Core\Entity\EntityAccessControlHandler class checks this
   * permission for all operations in its checkAccess() method. Entities with
   * more complex permissions can extend this class to do their own access
   * checks.
   *
   * @return string|bool
   */
  public function getAdminPermission();

  /**
   * Gets the permission granularity level.
   *
   * The allowed values are respectively "entity_type" or "bundle".
   *
   * @return string
   *   Whether a module exposing permissions for the current entity type
   *   should use entity-type level granularity or bundle level granularity.
   */
  public function getPermissionGranularity();

  /**
   * Gets the link templates using the URI template syntax.
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
   * - [entityType]: The entity type itself will also be a valid token for the
   *   ID of the entity. For instance, a placeholder of {node} used on the Node
   *   class.
   * - [bundleEntityType]: The bundle machine name itself. For instance, a
   *   placeholder of {node_type} used on the Node class.
   *
   * Specific entity types may also expand upon this list by overriding the
   * Entity::urlRouteParameters() method.
   *
   * @link http://www.iana.org/assignments/link-relations/link-relations.xml @endlink
   * @link http://tools.ietf.org/html/rfc6570 @endlink
   *
   * @return array
   */
  public function getLinkTemplates();

  /**
   * Gets the link template for a given key.
   *
   * @param string $key
   *   The link type.
   *
   * @return string|bool
   *   The path for this link, or FALSE if it doesn't exist.
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
   * @param string $path
   *   The route path to use for the link.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   Thrown when the path does not start with a leading slash.
   */
  public function setLinkTemplate($key, $path);

  /**
   * Gets the callback for the label of the entity.
   *
   * The function takes an entity and returns the label of the entity. Use
   * language() on the entity to get information on the requested language. The
   * entity label is the main string associated with an entity; for example, the
   * title of a node or the subject of a comment. If there is an entity object
   * property that defines the label, use the 'label' element of the
   * 'entity_keys' return value component to provide this information. If more
   * complex logic is needed to determine the label of an entity, you can
   * instead specify a callback function here, which will be called to determine
   * the entity label.
   *
   * @return callable|null
   *   The callback, or NULL if none exists.
   *
   * @deprecated in Drupal 8.0.x-dev and will be removed before Drupal 9.0.0.
   *   Use Drupal\Core\Entity\EntityInterface::label() for complex label
   *   generation as needed.
   *
   * @see \Drupal\Core\Entity\EntityInterface::label()
   * @see \Drupal\Core\Entity\EntityTypeInterface::setLabelCallback()
   * @see \Drupal\Core\Entity\EntityTypeInterface::hasLabelCallback()
   *
   * @todo Remove usages of label_callback https://www.drupal.org/node/2450793.
   */
  public function getLabelCallback();

  /**
   * Sets the label callback.
   *
   * @param callable $callback
   *   A callable that returns the label of the entity.
   *
   * @return $this
   *
   * @deprecated in Drupal 8.0.x-dev and will be removed before Drupal 9.0.0.
   *   Use EntityInterface::label() for complex label generation as needed.
   *
   * @see \Drupal\Core\Entity\EntityInterface::label()
   * @see \Drupal\Core\Entity\EntityTypeInterface::getLabelCallback()
   * @see \Drupal\Core\Entity\EntityTypeInterface::hasLabelCallback()
   */
  public function setLabelCallback($callback);

  /**
   * Indicates if a label callback exists.
   *
   * @return bool
   *
   * @deprecated in Drupal 8.0.x-dev and will be removed before Drupal 9.0.0.
   *   Use EntityInterface::label() for complex label generation as needed.
   *
   * @see \Drupal\Core\Entity\EntityInterface::label()
   * @see \Drupal\Core\Entity\EntityTypeInterface::getLabelCallback()
   * @see \Drupal\Core\Entity\EntityTypeInterface::setLabelCallback()
   */
  public function hasLabelCallback();

  /**
   * Gets the name of the entity type which provides bundles.
   *
   * @return string|null
   *   The name of the entity type which provides bundles, or NULL if the entity
   *   type does not have a bundle entity type.
   */
  public function getBundleEntityType();

  /**
   * Gets the entity type for which this entity provides bundles.
   *
   * It can be used by other modules to act accordingly; for example,
   * the Field UI module uses it to add operation links to manage fields and
   * displays.
   *
   * @return string|null
   *   The entity type for which this entity provides bundles, or NULL if does
   *   not provide bundles for another entity type.
   */
  public function getBundleOf();

  /**
   * Gets the label for the bundle.
   *
   * @return string
   *   The bundle label.
   */
  public function getBundleLabel();

  /**
   * Gets the name of the entity's base table.
   *
   * @todo Used by SqlContentEntityStorage only.
   *
   * @return string|null
   *   The name of the entity's base table, or NULL if none exists.
   */
  public function getBaseTable();

  /**
   * Indicates whether the entity data is internal.
   *
   * This can be used in a scenario when it is not desirable to expose data of
   * this entity type to an external system.
   *
   * The implications of this method are left to the discretion of the caller.
   * For example, a module providing an HTTP API may not expose entities of
   * this type or a custom entity reference field settings form may deprioritize
   * entities of this type in a select list.
   *
   * @return bool
   *   TRUE if the entity data is internal, FALSE otherwise.
   *
   * @see \Drupal\Core\TypedData\DataDefinitionInterface::isInternal()
   */
  public function isInternal();

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
   * Indicates whether the revision form fields should be added to the form.
   *
   * @return bool
   *   TRUE if the form field should be added, FALSE otherwise.
   */
  public function showRevisionUi();

  /**
   * Indicates whether entities of this type have revision support.
   *
   * @return bool
   */
  public function isRevisionable();

  /**
   * Gets the name of the entity's revision data table.
   *
   * @todo Used by SqlContentEntityStorage only.
   *
   * @return string|null
   *   The name of the entity type's revision data table, or NULL if none
   *   exists.
   */
  public function getRevisionDataTable();

  /**
   * Gets the name of the entity's revision table.
   *
   * @todo Used by SqlContentEntityStorage only.
   *
   * @return string|null
   *   The name of the entity type's revision table, or NULL if none exists.
   */
  public function getRevisionTable();

  /**
   * Gets the name of the entity's data table.
   *
   * @todo Used by SqlContentEntityStorage only.
   *
   * @return string|null
   *   The name of the entity type's data table, or NULL if none exists.
   */
  public function getDataTable();

  /**
   * Gets the human-readable name of the entity type.
   *
   * This label should be used to present a human-readable name of the
   * entity type.
   *
   * @return string
   *   The human-readable name of the entity type.
   */
  public function getLabel();

  /**
   * Gets the lowercase form of the human-readable entity type name.
   *
   * @return string
   *   The lowercase form of the human-readable entity type name.
   *
   * @see \Drupal\Core\Entity\EntityTypeInterface::getLabel()
   */
  public function getLowercaseLabel();

  /**
   * Gets the uppercase plural form of the name of the entity type.
   *
   * This should return a human-readable version of the name that can refer
   * to all the entities of the given type, collectively. An example usage of
   * this is the page title of a page devoted to a collection of entities such
   * as "Workflows" (instead of "Workflow entities").
   *
   * @return string
   *   The collection label.
   */
  public function getCollectionLabel();

  /**
   * Gets the indefinite singular form of the name of the entity type.
   *
   * This should return the human-readable name for a single instance of
   * the entity type. For example: "opportunity" (with the plural as
   * "opportunities"), "child" (with the plural as "children"), or "content
   * item" (with the plural as "content items").
   *
   * @return string
   *   The singular label.
   */
  public function getSingularLabel();

  /**
   * Gets the indefinite plural form of the name of the entity type.
   *
   * This should return the human-readable name for more than one instance of
   * the entity type. For example: "opportunities" (with the singular as
   * "opportunity"), "children" (with the singular as "child"), or "content
   * items" (with the singular as "content item").
   *
   * @return string
   *   The plural label.
   */
  public function getPluralLabel();

  /**
   * Gets the label's definite article form for use with a count of entities.
   *
   * This label should be used when the quantity of entities is provided. The
   * name should be returned in a form usable with a count of the
   * entities. For example: "1 opportunity", "5 opportunities", "1 child",
   * "6 children", "1 content item", "25 content items".
   *
   * @param int $count
   *   The item count to display if the plural form was requested.
   *
   * @return string
   *   The count label.
   */
  public function getCountLabel($count);

  /**
   * Gets a callable that can be used to provide the entity URI.
   *
   * This is only called if there is no matching link template for the link
   * relationship type, and there is no bundle-specific callback provided.
   *
   * @return callable|null
   *   A valid callback that is passed the entity or NULL if none is specified.
   */
  public function getUriCallback();

  /**
   * Sets a callable to use to provide the entity URI.
   *
   * @param callable $callback
   *   A callback to use to provide a URI for the entity.
   *
   * @return $this
   */
  public function setUriCallback($callback);

  /**
   * Gets the machine name of the entity type group.
   *
   * @return string
   */
  public function getGroup();

  /**
   * Gets the human-readable name of the entity type group.
   *
   * @return string
   */
  public function getGroupLabel();

  /**
   * The list cache contexts associated with this entity type.
   *
   * Enables code listing entities of this type to ensure that rendered listings
   * are varied as necessary, typically to ensure users of role A see other
   * entities listed than users of role B.
   *
   * @return string[]
   */
  public function getListCacheContexts();

  /**
   * The list cache tags associated with this entity type.
   *
   * Enables code listing entities of this type to ensure that newly created
   * entities show up immediately.
   *
   * @return string[]
   */
  public function getListCacheTags();

  /**
   * Gets the key that is used to store configuration dependencies.
   *
   * @return string
   *   The key to be used in configuration dependencies when storing
   *   dependencies on entities of this type.
   */
  public function getConfigDependencyKey();

  /**
   * Indicates whether this entity type is commonly used as a reference target.
   *
   * @return bool
   *   TRUE if the entity type is a common reference; FALSE otherwise.
   */
  public function isCommonReferenceTarget();

  /**
   * Gets an array of validation constraints.
   *
   * See \Drupal\Core\TypedData\DataDefinitionInterface::getConstraints() for
   * details on how constraints are defined.
   *
   * @return array[]
   *   An array of validation constraint definitions, keyed by constraint name.
   *   Each constraint definition can be used for instantiating
   *   \Symfony\Component\Validator\Constraint objects.
   *
   * @see \Symfony\Component\Validator\Constraint
   */
  public function getConstraints();

  /**
   * Sets the array of validation constraints for the FieldItemList.
   *
   * NOTE: This will overwrite any previously set constraints. In most cases
   * ContentEntityTypeInterface::addConstraint() should be used instead.
   * See \Drupal\Core\TypedData\DataDefinitionInterface::getConstraints() for
   * details on how constraints are defined.
   *
   * @param array $constraints
   *   An array of validation constraint definitions, keyed by constraint name.
   *   Each constraint definition can be used for instantiating
   *   \Symfony\Component\Validator\Constraint objects.
   *
   * @return $this
   *
   * @see \Symfony\Component\Validator\Constraint
   */
  public function setConstraints(array $constraints);

  /**
   * Adds a validation constraint.
   *
   * See \Drupal\Core\TypedData\DataDefinitionInterface::getConstraints() for
   * details on how constraints are defined.
   *
   * @param string $constraint_name
   *   The name of the constraint to add, i.e. its plugin id.
   * @param array|null $options
   *   The constraint options as required by the constraint plugin, or NULL.
   *
   * @return $this
   */
  public function addConstraint($constraint_name, $options = NULL);

  /**
   * Gets the config dependency info for this entity, if any exists.
   *
   * @param string $bundle
   *   The bundle name.
   *
   * @return array
   *   An associative array containing the following keys:
   *   - 'type': The config dependency type (e.g. 'module', 'config').
   *   - 'name': The name of the config dependency.
   */
  public function getBundleConfigDependency($bundle);

}
