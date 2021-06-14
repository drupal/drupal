<?php

namespace Drupal\layout_builder;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;

/**
 * Service class to track inline block usage.
 */
class InlineBlockUsage implements InlineBlockUsageInterface {

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
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getUnused($limit = 100) {
    $query = $this->database->select('inline_block_usage', 't');
    $query->fields('t', ['block_content_id']);
    $query->isNull('layout_entity_id');
    $query->isNull('layout_entity_type');
    return $query->range(0, $limit)->execute()->fetchCol();
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function deleteUsage(array $block_content_ids) {
    if (!empty($block_content_ids)) {
      $query = $this->database->delete('inline_block_usage')->condition('block_content_id', $block_content_ids, 'IN');
      $query->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUsage($block_content_id) {
    $query = $this->database->select('inline_block_usage');
    $query->condition('block_content_id', $block_content_id);
    $query->fields('inline_block_usage', ['layout_entity_id', 'layout_entity_type']);
    $query->range(0, 1);
    return $query->execute()->fetchObject();
  }

}
