<?php

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityField;
use Drupal\views\ResultRow;

/**
 * Views field display for commented entity.
 *
 * @ViewsField("commented_entity")
 */
class CommentedEntity extends EntityField {

  /**
   * Array of entities that has comments.
   *
   * We use this to load all the commented entities of same entity type at once
   * to the EntityStorageController static cache.
   *
   * @var array
   */
  protected $loadedCommentedEntities = [];

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    if (empty($this->loadedCommentedEntities)) {
      $result = $this->view->result;

      $entity_ids_per_type = [];
      foreach ($result as $value) {
        /** @var \Drupal\comment\CommentInterface $comment */
        if ($comment = $this->getEntity($value)) {
          $entity_ids_per_type[$comment->getCommentedEntityTypeId()][] = $comment->getCommentedEntityId();
        }
      }

      foreach ($entity_ids_per_type as $type => $ids) {
        $this->loadedCommentedEntities[$type] = $this->entityTypeManager->getStorage($type)->loadMultiple($ids);
      }
    }

    return parent::getItems($values);
  }

}
