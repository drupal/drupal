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
   * Loads a specific bundle entity.
   *
   * @param string $bundle_entity_type_id
   *   The bundle entity type ID.
   * @param string $bundle_id
   *   The bundle ID.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface|null
   *   The bundle entity.
   */
  public function loadBundleEntity($bundle_entity_type_id, $bundle_id);

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
   * Loads the latest revision of a specific entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int $entity_id
   *   The entity ID.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The latest entity revision or NULL, if the entity type / entity doesn't
   *   exist.
   */
  public function getLatestRevision($entity_type_id, $entity_id);

  /**
   * Returns the revision ID of the latest revision of the given entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int $entity_id
   *   The entity ID.
   *
   * @return int
   *   The revision ID of the latest revision for the specified entity, or
   *   NULL if there is no such entity.
   */
  public function getLatestRevisionId($entity_type_id, $entity_id);

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
   * Determines if an entity is a latest revision.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   A revisionable content entity.
   *
   * @return bool
   *   TRUE if the specified object is the latest revision of its entity,
   *   FALSE otherwise.
   */
  public function isLatestRevision(ContentEntityInterface $entity);

  /**
   * Determines if a forward revision exists for the specified entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity which may or may not have a forward revision.
   *
   * @return bool
   *   TRUE if this entity has forward revisions available, FALSE otherwise.
   */
  public function hasForwardRevision(ContentEntityInterface $entity);

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

}
