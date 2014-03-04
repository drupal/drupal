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
   * @return array
   *   An array of entity type labels, keyed by entity type name.
   */
  public function getEntityTypeLabels();

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
   * Creates a new storage controller instance.
   *
   * @param string $entity_type
   *   The entity type for this storage controller.
   *
   * @return \Drupal\Core\Entity\EntityStorageControllerInterface
   *   A storage controller instance.
   */
  public function getStorageController($entity_type);

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
   * Creates a new list controller instance.
   *
   * @param string $entity_type
   *   The entity type for this list controller.
   *
   * @return \Drupal\Core\Entity\EntityListControllerInterface
   *   A list controller instance.
   */
  public function getListController($entity_type);

  /**
   * Creates a new form controller instance.
   *
   * @param string $entity_type
   *   The entity type for this form controller.
   * @param string $operation
   *   The name of the operation to use, e.g., 'default'.
   *
   * @return \Drupal\Core\Entity\EntityFormControllerInterface
   *   A form controller instance.
   */
  public function getFormController($entity_type, $operation);

  /**
   * Clears static and persistent field definition caches.
   */
  public function clearCachedFieldDefinitions();

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
   * Returns the entity type info for a specific entity type.
   *
   * @param string $entity_type_id
   *   The ID of the entity type.
   * @param bool $exception_on_invalid
   *   (optional) If TRUE, an invalid entity type ID will throw an exception.
   *   Defaults to FALSE.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   *   Returns the entity type object, or NULL if the entity type ID is invalid
   *   and $exception_on_invalid is TRUE.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $entity_type_id is invalid and $exception_on_invalid is TRUE.
   */
  public function getDefinition($entity_type_id, $exception_on_invalid = FALSE);

  /**
   * Returns an array of entity type info, keyed by entity type name.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   An array of entity type objects.
   */
  public function getDefinitions();

}
