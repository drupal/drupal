<?php

namespace Drupal\content_moderation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormInterface;

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
   * Determines if an entity is one we should be moderating.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity we may be moderating.
   *
   * @return bool
   *   TRUE if this is an entity that we should act upon, FALSE otherwise.
   */
  public function isModeratableEntity(EntityInterface $entity);

  /**
   * Determines if an entity type has been marked as moderatable.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   An entity type object.
   *
   * @return bool
   *   TRUE if this entity type has been marked as moderatable, FALSE otherwise.
   */
  public function isModeratableEntityType(EntityTypeInterface $entity_type);

  /**
   * Determines if an entity type/bundle is one that will be moderated.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition to check.
   * @param string $bundle
   *   The bundle to check.
   *
   * @return bool
   *   TRUE if this is a bundle we want to moderate, FALSE otherwise.
   */
  public function isModeratableBundle(EntityTypeInterface $entity_type, $bundle);

  /**
   * Filters entity lists to just bundle definitions for revisionable entities.
   *
   * @param EntityTypeInterface[] $entity_types
   *   The master entity type list filter.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityTypeInterface[]
   *   An array of only the config entities we want to modify.
   */
  public function selectRevisionableEntityTypes(array $entity_types);

  /**
   * Filters entity lists to just the definitions for moderatable entities.
   *
   * An entity type is moderatable only if it is both revisionable and
   * bundleable.
   *
   * @param EntityTypeInterface[] $entity_types
   *   The master entity type list filter.
   *
   * @return \Drupal\Core\Entity\ContentEntityTypeInterface[]
   *   An array of only the content entity definitions we want to modify.
   */
  public function selectRevisionableEntities(array $entity_types);

  /**
   * Determines if config entity is a bundle for entities that may be moderated.
   *
   * This is the same check as exists in selectRevisionableEntityTypes(), but
   * that one cannot use the entity manager due to recursion and this one
   * doesn't have the entity list otherwise so must use the entity manager. The
   * alternative would be to call getDefinitions() on entityTypeManager and use
   * that in a sub-call, but that would be unnecessarily memory intensive.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if we want to add a Moderation operation to this entity, FALSE
   *   otherwise.
   */
  public function isBundleForModeratableEntity(EntityInterface $entity);

  /**
   * Determines if this form is for a moderated entity.
   *
   * @param \Drupal\Core\Form\FormInterface $form_object
   *   The form definition object for this form.
   *
   * @return bool
   *   TRUE if the form is for an entity that is subject to moderation, FALSE
   *   otherwise.
   */
  public function isModeratedEntityForm(FormInterface $form_object);

  /**
   * Determines if the form is the bundle edit of a revisionable entity.
   *
   * The logic here is not entirely clear, but seems to work. The form- and
   * entity-dereference chaining seems excessive but is what works.
   *
   * @param \Drupal\Core\Form\FormInterface $form_object
   *   The form definition object for this form.
   *
   * @return bool
   *   True if the form is the bundle edit form for an entity type that supports
   *   revisions, false otherwise.
   */
  public function isRevisionableBundleForm(FormInterface $form_object);

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
