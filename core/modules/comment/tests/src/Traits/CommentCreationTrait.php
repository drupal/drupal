<?php

declare(strict_types = 1);

namespace Drupal\Tests\comment\Traits;

use Drupal\comment\CommentInterface;
use Drupal\comment\CommentTypeInterface;

/**
 * Provides methods for comment entities creation in tests.
 */
trait CommentCreationTrait {

  /**
   * Creates a comment type entity.
   *
   * @param string $target_entity_type_id
   *   The target entity type ID.
   * @param array $values
   *   The entity creation values.
   *
   * @return \Drupal\comment\CommentTypeInterface
   *   The comment type entity.
   */
  protected function createCommentType(string $target_entity_type_id, array $values = []): CommentTypeInterface {
    $values['target_entity_type_id'] = $target_entity_type_id;
    $values += [
      'id' => 'comment',
      'label' => $this->randomString(),
      'description' => $this->randomString(),
    ];
    /** @var \Drupal\comment\CommentTypeInterface $comment_type */
    $comment_type = \Drupal::entityTypeManager()->getStorage('comment_type')->create($values);
    $comment_type->save();

    return $comment_type;
  }

  /**
   * Creates a test comment entity.
   *
   * @param array $values
   *   A list of values to be set on entity.
   *
   * @return \Drupal\comment\CommentInterface
   *   The comment entity.
   */
  protected function createComment(array $values = []): CommentInterface {
    /** @var \Drupal\comment\CommentStorageInterface $comment_storage */
    $comment_storage = \Drupal::entityTypeManager()->getStorage('comment');

    if (empty($values['entity_type']) || empty($values['entity_id'])) {
      if (empty($values['pid'])) {
        throw new \InvalidArgumentException('The parent comment ID value ($values["pid"]) is mandatory when $values["entity_type"] and/or $values["entity_id"] are missing.');
      }
      /** @var \Drupal\comment\CommentInterface $parent_comment */
      $parent_comment = $comment_storage->load($values['pid']);
      $values['entity_type'] = $parent_comment->getCommentedEntityTypeId();
      $values['entity_id'] = $parent_comment->getCommentedEntityId();
    }
    $values += [
      'comment_type' => 'comment',
      'name' => $this->randomString(),
      'hostname' => 'example.com',
      'homepage' => 'http://example.com',
      'mail' => "{$this->randomMachineName()}@example.com",
      'subject' => $this->randomString(),
      'field_name' => 'comment',
      'pid' => 0,
      'uid' => 0,
      'status' => TRUE,
    ];
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $comment_storage->create($values);
    $comment->save();

    return $comment;
  }

}
