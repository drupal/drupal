<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityManagerInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides an interface for entity type managers.
 */
interface EntityManagerInterface extends PluginManagerInterface {

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
   * Collects a lightweight map of fields across bundles.
   *
   * @return array
   *   An array keyed by entity type. Each value is an array which keys are
   *   field names and value is an array with two entries:
   *   - type: The field type.
   *   - bundles: The bundles in which the field appears.
   */
  public function getFieldMap();

  /**
   * Creates a new access controller instance.
   *
   * @param string $entity_type
   *   The entity type for this access controller.
   *
   * @return \Drupal\Core\Entity\EntityAccessControllerInterface.
   *   A access controller instance.
   */
  public function getAccessController($entity_type);

  /**
   * Returns the route information for an entity type's bundle.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return array
   *   An associative array with the following keys:
   *   - route_name: The name of the route.
   *   - route_parameters: (optional) An associative array of parameter names
   *     and values.
   */
  public function getAdminRouteInfo($entity_type_id, $bundle);

  /**
   * Creates a new storage instance.
   *
   * @param string $entity_type
   *   The entity type for this storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   A storage instance.
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
   *   A render controller instance.
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
   * Checks whether a certain entity type has a certain controller.
   *
   * @param string $entity_type
   *   The name of the entity type.
   * @param string $controller_type
   *   The name of the controller.
   *
   * @return bool
   *   Returns TRUE if the entity type has the controller, else FALSE.
   */
  public function hasController($entity_type, $controller_type);

  /**
   * Creates a new controller instance.
   *
   * @param string $entity_type
   *   The entity type for this controller.
   * @param string $controller_type
   *   The controller type to create an instance for.
   *
   * @return mixed
   *   A controller instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getController($entity_type, $controller_type);

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
   * @see \Drupal\Core\Language\LanguageManager::getFallbackCandidates()
   */
  public function getTranslationFromContext(EntityInterface $entity, $langcode = NULL, $context = array());

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   */
  public function getDefinition($entity_type_id, $exception_on_invalid = TRUE);

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

}
