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
   * Gets an array of content entity field definitions.
   *
   * If a bundle is passed, fields specific to this bundle are included. Entity
   * fields are always multi-valued, so 'list' is TRUE for each returned field
   * definition.
   *
   * @param string $entity_type
   *   The entity type to get field definitions for. Only entity types that
   *   implement \Drupal\Core\Entity\ContentEntityInterface are supported.
   * @param string $bundle
   *   (optional) The entity bundle for which to get field definitions. If NULL
   *   is passed, no bundle-specific fields are included. Defaults to NULL.
   *
   * @return array
   *   An array of field definitions of entity fields, keyed by field
   *   name. In addition to the typed data definition keys as described at
   *   \Drupal\Core\TypedData\TypedDataManager::create() the following keys are
   *   supported:
   *   - queryable: Whether the field is queryable via QueryInterface.
   *     Defaults to TRUE if 'computed' is FALSE or not set, to FALSE otherwise.
   *   - translatable: Whether the field is translatable. Defaults to FALSE.
   *   - configurable: A boolean indicating whether the field is configurable
   *     via field.module. Defaults to FALSE.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::create()
   * @see \Drupal\Core\Entity\EntityManagerInterface::getFieldDefinitionsByConstraints()
   */
  public function getFieldDefinitions($entity_type, $bundle = NULL);

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
   * @param string $entity_type
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
  public function getAdminRouteInfo($entity_type, $bundle);

  /**
   * Gets an array of entity field definitions based on validation constraints.
   *
   * @param string $entity_type
   *   The entity type to get field definitions for.
   * @param array $constraints
   *   An array of entity constraints as used for entities in typed data
   *   definitions, i.e. an array optionally including a 'Bundle' key.
   *   For example the constraints used by an entity reference could be:
   *
   * @code
   *   array(
   *     'Bundle' => 'article',
   *   )
   * @endcode
   *
   * @return array
   *   An array of field definitions of entity fields, keyed by field
   *   name.
   *
   * @see \Drupal\Core\Entity\EntityManagerInterface::getFieldDefinitions()
   */
  public function getFieldDefinitionsByConstraints($entity_type, array $constraints);

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
   * Returns an entity controller class.
   *
   * @param string $entity_type
   *   The name of the entity type
   * @param string $controller_type
   *   The name of the controller.
   * @param string|null $nested
   *   (optional) If this controller definition is nested, the name of the key.
   *   Defaults to NULL.
   *
   * @return string
   *   The class name for this controller instance.
   */
  public function getControllerClass($entity_type, $controller_type, $nested = NULL);

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
   * Returns the administration path for an entity type's bundle.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return string
   *   The administration path for an entity type bundle, if it exists.
   */
  public function getAdminPath($entity_type, $bundle);

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
   * Returns the built and processed entity form for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be created or edited.
   * @param string $operation
   *   (optional) The operation identifying the form variation to be returned.
   *   Defaults to 'default'.
   * @param array $form_state
   *   (optional) An associative array containing the current state of the form.
   *   Use this to pass additional information to the form, such as the
   *   langcode. Defaults to an empty array.
   *
   * @code
   *   $form_state['langcode'] = $langcode;
   *   $manager = \Drupal::entityManager();
   *   $form = $manager->getForm($entity, 'default', $form_state);
   * @endcode
   *
   * @return array
   *   The processed form for the given entity and operation.
   */
  public function getForm(EntityInterface $entity, $operation = 'default', array $form_state = array());

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

}
