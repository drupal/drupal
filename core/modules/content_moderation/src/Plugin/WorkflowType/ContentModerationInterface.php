<?php

namespace Drupal\content_moderation\Plugin\WorkflowType;

use Drupal\workflows\WorkflowTypeInterface;

/**
 * Interface for ContentModeration WorkflowType plugin.
 */
interface ContentModerationInterface extends WorkflowTypeInterface {

  /**
   * Gets the entity types the workflow is applied to.
   *
   * @return string[]
   *   The entity types the workflow is applied to.
   */
  public function getEntityTypes();

  /**
   * Gets any bundles the workflow is applied to for the given entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID to get the bundles for.
   *
   * @return string[]
   *   The bundles of the entity type the workflow is applied to or an empty
   *   array if the entity type is not applied to the workflow.
   */
  public function getBundlesForEntityType($entity_type_id);

  /**
   * Checks if the workflow applies to the supplied entity type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID to check.
   * @param string $bundle_id
   *   The bundle ID to check.
   *
   * @return bool
   *   TRUE if the workflow applies to the supplied entity type ID and bundle
   *   ID. FALSE if not.
   */
  public function appliesToEntityTypeAndBundle($entity_type_id, $bundle_id);

  /**
   * Removes an entity type ID / bundle ID from the workflow.
   *
   * @param string $entity_type_id
   *   The entity type ID to remove.
   * @param string $bundle_id
   *   The bundle ID to remove.
   */
  public function removeEntityTypeAndBundle($entity_type_id, $bundle_id);

  /**
   * Add an entity type ID / bundle ID to the workflow.
   *
   * @param string $entity_type_id
   *   The entity type ID to add. It is responsibility of the caller to provide
   *   a valid entity type ID.
   * @param string $bundle_id
   *   The bundle ID to add. It is responsibility of the caller to provide a
   *   valid bundle ID.
   */
  public function addEntityTypeAndBundle($entity_type_id, $bundle_id);

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   Content Moderation uses this parameter to determine the initial state
   *   based on publishing status.
   */
  public function getInitialState($entity = NULL);

}
