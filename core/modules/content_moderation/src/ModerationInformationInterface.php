<?php

namespace Drupal\content_moderation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Interface for moderation_information service.
 */
interface ModerationInformationInterface {

  /**
   * Determines if an entity is moderated.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity we may be moderating.
   *
   * @return bool
   *   TRUE if this entity is moderated, FALSE otherwise.
   */
  public function isModeratedEntity(EntityInterface $entity);

  /**
   * Determines if an entity type can have moderated entities.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   An entity type object.
   *
   * @return bool
   *   TRUE if this entity type can have moderated entities, FALSE otherwise.
   */
  public function canModerateEntitiesOfEntityType(EntityTypeInterface $entity_type);

  /**
   * Determines if an entity type/bundle entities should be moderated.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition to check.
   * @param string $bundle
   *   The bundle to check.
   *
   * @return bool
   *   TRUE if an entity type/bundle entities should be moderated, FALSE
   *   otherwise.
   */
  public function shouldModerateEntitiesOfBundle(EntityTypeInterface $entity_type, $bundle);

  /**
   * Determines if an entity type has at least one moderated bundle.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition to check.
   *
   * @return bool
   *   TRUE if an entity type has a moderated bundle, FALSE otherwise.
   */
  public function isModeratedEntityType(EntityTypeInterface $entity_type);

  /**
   * Returns the revision ID of the default revision for the specified entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int $entity_id
   *   The entity ID.
   *
   * @return int
   *   The revision ID of the default revision, or NULL if the entity was
   *   not found.
   */
  public function getDefaultRevisionId($entity_type_id, $entity_id);

  /**
   * Returns the revision translation affected translation of a revision.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The revision translation affected translation.
   */
  public function getAffectedRevisionTranslation(ContentEntityInterface $entity);

  /**
   * Determines if a pending revision exists for the specified entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity which may or may not have a pending revision.
   *
   * @return bool
   *   TRUE if this entity has pending revisions available, FALSE otherwise.
   */
  public function hasPendingRevision(ContentEntityInterface $entity);

  /**
   * Determines if an entity is "live".
   *
   * A "live" entity revision is one whose latest revision is also the default,
   * and whose moderation state, if any, is a published state.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if the specified entity is a live revision, FALSE otherwise.
   */
  public function isLiveRevision(ContentEntityInterface $entity);

  /**
   * Determines if the default revision for the given entity is published.
   *
   * The default revision is the same as the entity retrieved by "default" from
   * the storage handler. If the entity is translated, check if any of the
   * translations are published.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being saved.
   *
   * @return bool
   *   TRUE if the default revision is published. FALSE otherwise.
   */
  public function isDefaultRevisionPublished(ContentEntityInterface $entity);

  /**
   * Gets the workflow for the given content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity to get the workflow for.
   *
   * @return \Drupal\workflows\WorkflowInterface|null
   *   The workflow entity. NULL if there is no workflow.
   */
  public function getWorkflowForEntity(ContentEntityInterface $entity);

  /**
   * Gets the workflow for the given entity type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle_id
   *   The entity bundle ID.
   *
   * @return \Drupal\workflows\WorkflowInterface|null
   *   The associated workflow. NULL if there is no workflow.
   */
  public function getWorkflowForEntityTypeAndBundle($entity_type_id, $bundle_id);

  /**
   * Gets unsupported features for a given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to get the unsupported features for.
   *
   * @return array
   *   An array of unsupported features for this entity type.
   */
  public function getUnsupportedFeatures(EntityTypeInterface $entity_type);

  /**
   * Gets the original or initial state of the given entity.
   *
   * When a state is being validated, the original state is used to validate
   * that a valid transition exists for target state and the user has access
   * to the transition between those two states. If the entity has been
   * moderated before, we can load the original unmodified revision and
   * translation for this state.
   *
   * If the entity is new we need to load the initial state from the workflow.
   * Even if a value was assigned to the moderation_state field, the initial
   * state is used to compute an appropriate transition for the purposes of
   * validation.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity to get the workflow for.
   *
   * @return \Drupal\content_moderation\ContentModerationState
   *   The original or default moderation state.
   */
  public function getOriginalState(ContentEntityInterface $entity);

}
