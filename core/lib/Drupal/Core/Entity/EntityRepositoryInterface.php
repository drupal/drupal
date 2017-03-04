<?php

namespace Drupal\Core\Entity;

/**
 * Provides an interface for an entity repository.
 */
interface EntityRepositoryInterface {

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
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object, or NULL if there is no entity with the given UUID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown in case the requested entity type does not support UUIDs.
   */
  public function loadEntityByUuid($entity_type_id, $uuid);

  /**
   * Loads an entity by the config target identifier.
   *
   * @param string $entity_type_id
   *   The entity type ID to load from.
   * @param string $target
   *   The configuration target to load, as returned from
   *   \Drupal\Core\Entity\EntityInterface::getConfigTarget().
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object, or NULL if there is no entity with the given config
   *   target identifier.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if the target identifier is a UUID but the entity type does not
   *   support UUIDs.
   *
   * @see \Drupal\Core\Entity\EntityInterface::getConfigTarget()
   */
  public function loadEntityByConfigTarget($entity_type_id, $target);

  /**
   * Gets the entity translation to be used in the given context.
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
  public function getTranslationFromContext(EntityInterface $entity, $langcode = NULL, $context = []);

}
