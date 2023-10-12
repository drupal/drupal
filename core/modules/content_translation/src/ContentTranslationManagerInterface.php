<?php

namespace Drupal\content_translation;

use Drupal\Core\Entity\EntityInterface;

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
   * Returns an instance of the Content translation handler.
   *
   * @param string $entity_type_id
   *   The type of the entity being translated.
   *
   * @return \Drupal\content_translation\ContentTranslationHandlerInterface
   *   An instance of the content translation handler.
   */
  public function getTranslationHandler($entity_type_id);

  /**
   * Returns an instance of the Content translation metadata.
   *
   * @param \Drupal\Core\Entity\EntityInterface $translation
   *   The entity translation whose metadata needs to be retrieved.
   *
   * @return \Drupal\content_translation\ContentTranslationMetadataWrapperInterface
   *   An instance of the content translation metadata.
   */
  public function getTranslationMetadata(EntityInterface $translation);

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
   * @return bool
   *   TRUE if the specified bundle is translatable. If no bundle is provided
   *   returns TRUE if at least one of the entity bundles is translatable.
   */
  public function isEnabled($entity_type_id, $bundle = NULL);

}
