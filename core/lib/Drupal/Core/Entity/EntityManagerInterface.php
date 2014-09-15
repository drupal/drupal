<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityManagerInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;

/**
 * Provides an interface for entity type managers.
 */
interface EntityManagerInterface extends PluginManagerInterface, EntityTypeListenerInterface, FieldStorageDefinitionListenerInterface {

  /**
   * Builds a list of entity type labels suitable for a Form API options list.
   *
   * @param bool $group
   *   (optional) Whether to group entity types by plugin group (e.g. 'content',
   *   'config'). Defaults to FALSE.
   *
   * @return array
   *   An array of entity type labels, keyed by entity type name.
   */
  public function getEntityTypeLabels($group = FALSE);

  /**
   * Gets the base field definitions for a content entity type.
   *
   * Only fields that are not specific to a given bundle or set of bundles are
   * returned. This excludes configurable fields, as they are always attached
   * to a specific bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID. Only entity types that implement
   *   \Drupal\Core\Entity\ContentEntityInterface are supported.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The array of base field definitions for the entity type, keyed by field
   *   name.
   *
   * @throws \LogicException
   *   Thrown if one of the entity keys is flagged as translatable.
   */
  public function getBaseFieldDefinitions($entity_type_id);

  /**
   * Gets the field definitions for a specific bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID. Only entity types that implement
   *   \Drupal\Core\Entity\ContentEntityInterface are supported.
   * @param string $bundle
   *   The bundle.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The array of field definitions for the bundle, keyed by field name.
   */
  public function getFieldDefinitions($entity_type_id, $bundle);

  /**
   * Gets the field storage definitions for a content entity type.
   *
   * This returns all field storage definitions for base fields and bundle
   * fields of an entity type. Note that field storage definitions of a base
   * field equal the full base field definition (i.e. they implement
   * FieldDefinitionInterface), while the storage definitions for bundle fields
   * may implement FieldStorageDefinitionInterface only.
   *
   * @param string $entity_type_id
   *   The entity type ID. Only content entities are supported.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface[]
   *   The array of field storage definitions for the entity type, keyed by
   *   field name.
   *
   * @see \Drupal\Core\Field\FieldStorageDefinitionInterface
   */
  public function getFieldStorageDefinitions($entity_type_id);

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
   * Returns a lightweight map of fields across bundles.
   *
   * @return array
   *   An array keyed by entity type. Each value is an array which keys are
   *   field names and value is an array with two entries:
   *   - type: The field type.
   *   - bundles: The bundles in which the field appears.
   */
  public function getFieldMap();

  /**
   * Returns a lightweight map of fields across bundles filtered by field type.
   *
   * @param string $field_type
   *   The field type to filter by.
   *
   * @return array
   *   An array keyed by entity type. Each value is an array which keys are
   *   field names and value is an array with two entries:
   *   - type: The field type.
   *   - bundles: The bundles in which the field appears.
   */
  public function getFieldMapByFieldType($field_type);

  /**
   * Creates a new access control handler instance.
   *
   * @param string $entity_type
   *   The entity type for this access control handler.
   *
   * @return \Drupal\Core\Entity\EntityAccessControlHandlerInterface.
   *   A access control handler instance.
   */
  public function getAccessControlHandler($entity_type);

  /**
   * Creates a new storage instance.
   *
   * @param string $entity_type
   *   The entity type for this storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   A storage instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getStorage($entity_type);

  /**
   * Get the bundle info of all entity types.
   *
   * @return array
   *   An array of all bundle information.
   */
  public function getAllBundleInfo();

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions();

  /**
   * Clears static and persistent field definition caches.
   */
  public function clearCachedFieldDefinitions();

  /**
   * Clears static and persistent bundles.
   */
  public function clearCachedBundles();

  /**
   * Creates a new view builder instance.
   *
   * @param string $entity_type
   *   The entity type for this view builder.
   *
   * @return \Drupal\Core\Entity\EntityViewBuilderInterface.
   *   A view builder instance.
   */
  public function getViewBuilder($entity_type);

  /**
   * Creates a new entity list builder.
   *
   * @param string $entity_type
   *   The entity type for this list builder.
   *
   * @return \Drupal\Core\Entity\EntityListBuilderInterface
   *   An entity list builder instance.
   */
  public function getListBuilder($entity_type);

  /**
   * Creates a new form instance.
   *
   * @param string $entity_type
   *   The entity type for this form.
   * @param string $operation
   *   The name of the operation to use, e.g., 'default'.
   *
   * @return \Drupal\Core\Entity\EntityFormInterface
   *   A form instance.
   */
  public function getFormObject($entity_type, $operation);

  /**
   * Checks whether a certain entity type has a certain handler.
   *
   * @param string $entity_type
   *   The name of the entity type.
   * @param string $handler_type
   *   The name of the handler.
   *
   * @return bool
   *   Returns TRUE if the entity type has the handler, else FALSE.
   */
  public function hasHandler($entity_type, $handler_type);

  /**
   * Creates a new handler instance.
   *
   * @param string $entity_type
   *   The entity type for this controller.
   * @param string $handler_type
   *   The controller type to create an instance for.
   *
   * @return mixed
   *   A handler instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getHandler($entity_type, $handler_type);

  /**
   * Get the bundle info of an entity type.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return array
   *   Returns the bundle information for the specified entity type.
   */
  public function getBundleInfo($entity_type);

  /**
   * Retrieves the "extra fields" for a bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle name.
   *
   * @return array
   *   A nested array of 'pseudo-field' elements. Each list is nested within the
   *   following keys: entity type, bundle name, context (either 'form' or
   *   'display'). The keys are the name of the elements as appearing in the
   *   renderable array (either the entity form or the displayed entity). The
   *   value is an associative array:
   *   - label: The human readable name of the element. Make sure you sanitize
   *     this appropriately.
   *   - description: A short description of the element contents.
   *   - weight: The default weight of the element.
   *   - visible: (optional) The default visibility of the element. Defaults to
   *     TRUE.
   *   - edit: (optional) String containing markup (normally a link) used as the
   *     element's 'edit' operation in the administration interface. Only for
   *     'form' context.
   *   - delete: (optional) String containing markup (normally a link) used as the
   *     element's 'delete' operation in the administration interface. Only for
   *     'form' context.
   */
  public function getExtraFields($entity_type_id, $bundle);

  /**
   * Returns the entity translation to be used in the given context.
   *
   * This will check whether a translation for the desired language is available
   * and if not, it will fall back to the most appropriate translation based on
   * the provided context.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose translation will be returned.
   * @param string $langcode
   *   (optional) The language of the current context. Defaults to the current
   *   content language.
   * @param array $context
   *   (optional) An associative array of arbitrary data that can be useful to
   *   determine the proper fallback sequence.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   An entity object for the translated data.
   *
   * @see \Drupal\Core\Language\LanguageManagerInterface::getFallbackCandidates()
   */
  public function getTranslationFromContext(EntityInterface $entity, $langcode = NULL, $context = array());

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   */
  public function getDefinition($entity_type_id, $exception_on_invalid = TRUE);

  /**
   * Returns the entity type definition in its most recently installed state.
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
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   */
  public function getDefinitions();

  /**
   * Returns the entity view mode info for all entity types.
   *
   * @return array
   *   The view mode info for all entity types.
   */
  public function getAllViewModes();

  /**
   * Returns the entity view mode info for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type whose view mode info should be returned.
   *
   * @return array
   *   The view mode info for a specific entity type.
   */
  public function getViewModes($entity_type_id);

  /**
   * Returns the entity form mode info for all entity types.
   *
   * @return array
   *   The form mode info for all entity types.
   */
  public function getAllFormModes();

  /**
   * Returns the entity form mode info for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type whose form mode info should be returned.
   *
   * @return array
   *   The form mode info for a specific entity type.
   */
  public function getFormModes($entity_type_id);

  /**
   * Returns an array of view mode options.
   *
   * @param string $entity_type_id
   *   The entity type whose view mode options should be returned.
   * @param bool $include_disabled
   *   Force to include disabled view modes. Defaults to FALSE.
   *
   * @return array
   *   An array of view mode labels, keyed by the display mode ID.
   */
  public function getViewModeOptions($entity_type_id, $include_disabled = FALSE);

  /**
   * Returns an array of form mode options.
   *
   * @param string $entity_type_id
   *   The entity type whose form mode options should be returned.
   * @param bool $include_disabled
   *   Force to include disabled form modes. Defaults to FALSE.
   *
   * @return array
   *   An array of form mode labels, keyed by the display mode ID.
   */
  public function getFormModeOptions($entity_type_id, $include_disabled = FALSE);

  /**
   * Loads an entity by UUID.
   *
   * Note that some entity types may not support UUIDs.
   *
   * @param string $entity_type_id
   *   The entity type ID to load from.
   * @param string $uuid
   *   The UUID of the entity to load.
   *
   * @return \Drupal\Core\Entity\EntityInterface|FALSE
   *   The entity object, or FALSE if there is no entity with the given UUID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown in case the requested entity type does not support UUIDs.
   */
  public function loadEntityByUuid($entity_type_id, $uuid);

  /**
   * Returns the entity type ID based on the class that is called on.
   *
   * Compares the class this is called on against the known entity classes
   * and returns the entity type ID of a direct match or a subclass as fallback,
   * to support entity type definitions that were altered.
   *
   * @param string $class_name
   *   Class name to use for searching the entity type ID.
   *
   * @return string
   *   The entity type ID.
   *
   * @throws \Drupal\Core\Entity\Exception\AmbiguousEntityClassException
   *   Thrown when multiple subclasses correspond to the called class.
   * @throws \Drupal\Core\Entity\Exception\NoCorrespondingEntityClassException
   *   Thrown when no entity class corresponds to the called class.
   *
   * @see \Drupal\Core\Entity\Entity::load()
   * @see \Drupal\Core\Entity\Entity::loadMultiple()
   */
  public function getEntityTypeFromClass($class_name);

  /**
   * Reacts to the creation of a entity bundle.
   *
   * @param string $entity_type_id
   *   The entity type to which the bundle is bound; e.g. 'node' or 'user'.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @see entity_crud
   */
  public function onBundleCreate($entity_type_id, $bundle);

  /**
   * Reacts to the rename of a entity bundle.
   *
   * @param string $entity_type_id
   *   The entity type to which the bundle is bound; e.g. 'node' or 'user'.
   * @param string $bundle_old
   *   The previous name of the bundle.
   * @param string $bundle_new
   *   The new name of the bundle.
   *
   * @see entity_crud
   */
  public function onBundleRename($entity_type_id, $bundle_old, $bundle_new);

  /**
   * Reacts to the deletion of a entity bundle.
   *
   * @param string $entity_type_id
   *   The entity type to which the bundle is bound; e.g. 'node' or 'user'.
   * @param string $bundle
   *   The bundle that was just deleted.
   *
   * @see entity_crud
   */
  public function onBundleDelete($entity_type_id, $bundle);

}
