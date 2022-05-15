<?php

namespace Drupal\layout_builder;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for tracking inline block usage.
 */
interface InlineBlockUsageInterface {

  /**
   * Adds a usage record.
   *
   * @param int $block_content_id
   *   The block content ID.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The layout entity.
   */
  public function addUsage($block_content_id, EntityInterface $entity);

  /**
   * Gets unused inline block IDs.
   *
   * @param int $limit
   *   The maximum number of block content entity IDs to return.
   *
   * @return int[]
   *   The entity IDs.
   */
  public function getUnused($limit = 100);

  /**
   * Remove usage record by layout entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The layout entity.
   */
  public function removeByLayoutEntity(EntityInterface $entity);

  /**
   * Delete the inline blocks' the usage records.
   *
   * @param int[] $block_content_ids
   *   The block content entity IDs.
   */
  public function deleteUsage(array $block_content_ids);

  /**
   * Gets usage record for inline block by ID.
   *
   * @param int $block_content_id
   *   The block content entity ID.
   *
   * @return object|false
   *   The usage record with properties layout_entity_id and layout_entity_type
   *   or FALSE if there is no usage.
   */
  public function getUsage($block_content_id);

}
