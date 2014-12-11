<?php

/**
 * @file
 * Contains \Drupal\content_translation\ContentTranslationManagerInterface.
 */

namespace Drupal\content_translation;

/**
 * Provides an interface for common functionality for content translation.
 */
interface ContentTranslationManagerInterface {

  /**
   * Gets the entity types that support content translation.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   An array of entity types that support content translation.
   */
  public function getSupportedEntityTypes();

  /**
   * Checks whether an entity type supports translation.
   *
   * @param string $entity_type_id
   *   The entity type.
   *
   * @return bool
   *   TRUE if an entity type is supported, FALSE otherwise.
   */
  public function isSupported($entity_type_id);

  /**
   * Sets the value for translatability of the given entity type bundle.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $bundle
   *   The bundle of the entity.
   * @param bool $value
   *   The boolean value we need to save.
   */
  public function setEnabled($entity_type_id, $bundle, $value);

  /**
   * Determines whether the given entity type is translatable.
   *
   * @param string $entity_type_id
   *   The type of the entity.
   * @param string $bundle
   *   (optional) The bundle of the entity. If no bundle is provided, all the
   *   available bundles are checked.
   *
   * @returns bool
   *   TRUE if the specified bundle is translatable. If no bundle is provided
   *   returns TRUE if at least one of the entity bundles is translatable.
   *
   */
  public function isEnabled($entity_type_id, $bundle = NULL);

}
