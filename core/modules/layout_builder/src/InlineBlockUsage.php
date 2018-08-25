<?php

namespace Drupal\layout_builder;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;

/**
 * Service class to track inline block usage.
 *
 * @internal
 */
class InlineBlockUsage {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Creates an InlineBlockUsage object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Adds a usage record.
   *
   * @param int $block_content_id
   *   The block content id.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The layout entity.
   */
  public function addUsage($block_content_id, EntityInterface $entity) {
    $this->database->merge('inline_block_usage')
      ->keys([
        'block_content_id' => $block_content_id,
        'layout_entity_id' => $entity->id(),
        'layout_entity_type' => $entity->getEntityTypeId(),
      ])->execute();
  }

  /**
   * Gets unused inline block IDs.
   *
   * @param int $limit
   *   The maximum number of block content entity IDs to return.
   *
   * @return int[]
   *   The entity IDs.
   */
  public function getUnused($limit = 100) {
    $query = $this->database->select('inline_block_usage', 't');
    $query->fields('t', ['block_content_id']);
    $query->isNull('layout_entity_id');
    $query->isNull('layout_entity_type');
    return $query->range(0, $limit)->execute()->fetchCol();
  }

  /**
   * Remove usage record by layout entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The layout entity.
   */
  public function removeByLayoutEntity(EntityInterface $entity) {
    $query = $this->database->update('inline_block_usage')
      ->fields([
        'layout_entity_type' => NULL,
        'layout_entity_id' => NULL,
      ]);
    $query->condition('layout_entity_type', $entity->getEntityTypeId());
    $query->condition('layout_entity_id', $entity->id());
    $query->execute();
  }

  /**
   * Delete the inline blocks' the usage records.
   *
   * @param int[] $block_content_ids
   *   The block content entity IDs.
   */
  public function deleteUsage(array $block_content_ids) {
    $query = $this->database->delete('inline_block_usage')->condition('block_content_id', $block_content_ids, 'IN');
    $query->execute();
  }

  /**
   * Gets usage record for inline block by ID.
   *
   * @param int $block_content_id
   *   The block content entity ID.
   *
   * @return object
   *   The usage record with properties layout_entity_id and layout_entity_type.
   */
  public function getUsage($block_content_id) {
    $query = $this->database->select('inline_block_usage');
    $query->condition('block_content_id', $block_content_id);
    $query->fields('inline_block_usage', ['layout_entity_id', 'layout_entity_type']);
    $query->range(0, 1);
    return $query->execute()->fetchObject();
  }

}
