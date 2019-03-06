<?php

namespace Drupal\Core\Entity;

/**
 * Provides an interface for an entity repository.
 */
interface EntityRepositoryInterface {

  const CONTEXT_ID_LEGACY_CONTEXT_OPERATION = '@entity.repository:legacy_context_operation';

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

  /**
   * Retrieves the active entity variant matching the specified context.
   *
   * If an entity type is revisionable and/or translatable, which entity variant
   * should be handled depends on the context in which the manipulation happens.
   * Based on the specified contextual information, revision and translation
   * negotiation needs to be performed to return the active variant, that is the
   * most up-to-date entity variant in the context scope. This may or may not be
   * an entity variant intended for unprivileged user consumption, in fact it
   * might be a work in progress containing yet to be published information. The
   * active variant should always be retrieved when editing an entity, both in
   * form and in REST workflows, or previewing the related changes.
   *
   * The negotiation process will not perform any access check, so it is the
   * responsibility of the caller to verify that the user manipulating the
   * entity variant is actually allowed to do so.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param int|string $entity_id
   *   An entity identifier.
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   (optional) An associative array of objects representing the contexts the
   *   entity will be edited in keyed by fully qualified context ID. Defaults to
   *   the currently available contexts.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   An entity object variant or NULL if the entity does not exist.
   */
  public function getActive($entity_type_id, $entity_id, array $contexts = NULL);

  /**
   * Retrieves the active entity variants matching the specified context.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param int[]|string[] $entity_ids
   *   An array of entity identifiers.
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   (optional) An associative array of objects representing the contexts the
   *   entity will be edited in keyed by fully qualified context ID. Defaults to
   *   the currently available contexts.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   An array of entity object variants keyed by entity ID.
   *
   * @see getActive()
   */
  public function getActiveMultiple($entity_type_id, array $entity_ids, array $contexts = NULL);

  /**
   * Retrieves the canonical entity variant matching the specified context.
   *
   * If an entity type is revisionable and/or translatable, which entity variant
   * should be handled depends on the context in which the manipulation happens.
   * This will return the fittest entity variant intended for unprivileged user
   * consumption matching the specified context. This is typically the variant
   * that would be displayed on the entity's canonical route.
   *
   * The negotiation process will not perform any access check, so it is the
   * responsibility of the caller to verify that the user manipulating the
   * entity variant is actually allowed to do so.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param int|string $entity_id
   *   An entity identifier.
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   (optional) An associative array of objects representing the contexts the
   *   entity will be edited in keyed by fully qualified context ID. Defaults to
   *   the currently available contexts.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   An entity object variant or NULL if the entity does not exist.
   */
  public function getCanonical($entity_type_id, $entity_id, array $contexts = NULL);

  /**
   * Retrieves the canonical entity variants matching the specified context.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param int[]|string[] $entity_ids
   *   An array of entity identifiers.
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   (optional) An associative array of objects representing the contexts the
   *   entity will be edited in keyed by fully qualified context ID. Defaults to
   *   the currently available contexts.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   An array of entity object variants keyed by entity ID.
   *
   * @see getCanonical()
   */
  public function getCanonicalMultiple($entity_type_id, array $entity_ids, array $contexts = NULL);

}
