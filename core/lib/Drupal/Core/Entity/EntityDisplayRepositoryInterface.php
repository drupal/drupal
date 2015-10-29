<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityDisplayRepositoryInterface.
 */

namespace Drupal\Core\Entity;

/**
 * Provides an interface for an entity display repository.
 */
interface EntityDisplayRepositoryInterface {

  /**
   * Gets the entity view mode info for all entity types.
   *
   * @return array
   *   The view mode info for all entity types.
   */
  public function getAllViewModes();

  /**
   * Gets the entity view mode info for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type whose view mode info should be returned.
   *
   * @return array
   *   The view mode info for a specific entity type.
   */
  public function getViewModes($entity_type_id);

  /**
   * Gets the entity form mode info for all entity types.
   *
   * @return array
   *   The form mode info for all entity types.
   */
  public function getAllFormModes();

  /**
   * Gets the entity form mode info for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type whose form mode info should be returned.
   *
   * @return array
   *   The form mode info for a specific entity type.
   */
  public function getFormModes($entity_type_id);

  /**
   * Gets an array of view mode options.
   *
   * @param string $entity_type_id
   *   The entity type whose view mode options should be returned.
   *
   * @return array
   *   An array of view mode labels, keyed by the display mode ID.
   */
  public function getViewModeOptions($entity_type_id);

  /**
   * Gets an array of form mode options.
   *
   * @param string $entity_type_id
   *   The entity type whose form mode options should be returned.
   *
   * @return array
   *   An array of form mode labels, keyed by the display mode ID.
   */
  public function getFormModeOptions($entity_type_id);

  /**
   * Returns an array of enabled view mode options by bundle.
   *
   * @param string $entity_type_id
   *   The entity type whose view mode options should be returned.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return array
   *   An array of view mode labels, keyed by the display mode ID.
   */
  public function getViewModeOptionsByBundle($entity_type_id, $bundle);

  /**
   * Returns an array of enabled form mode options by bundle.
   *
   * @param string $entity_type_id
   *   The entity type whose form mode options should be returned.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return array
   *   An array of form mode labels, keyed by the display mode ID.
   */
  public function getFormModeOptionsByBundle($entity_type_id, $bundle);

  /**
   * Clears the gathered display mode info.
   *
   * @return $this
   */
  public function clearDisplayModeInfo();

}
