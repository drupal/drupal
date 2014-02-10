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

}
