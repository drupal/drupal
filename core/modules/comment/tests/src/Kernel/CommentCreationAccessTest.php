<?php

declare(strict_types = 1);

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\comment\Traits\CommentCreationTrait;

/**
 * Test comment creation access control.
 *
 * @group comment
 */
class CommentCreationAccessTest extends KernelTestBase {

  use CommentCreationTrait;
  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'comment_test',
    'entity_test',
    'field',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['user', 'comment']);
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installEntitySchema('comment');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');

    $this->createCommentType('entity_test', ['id' => 'comment']);
    $this->addDefaultCommentField('entity_test', 'entity_test');
  }

  /**
   * Tests that context is passed to the comment creation access control.
   *
   * @covers \Drupal\comment\Entity\Comment::access
   * @covers \Drupal\comment\CommentFieldItemList::access
   */
  public function testContextForCommentCreationAccessCheck(): void {
    $state = $this->container->get('state');

    // Create a testing host entity.
    $entity = EntityTest::create();
    $entity->save();

    $comment = $this->createComment([
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
    ]);

    /** @var \Drupal\comment\CommentFieldItemList $field */
    $field = $entity->get('comment');

    // Check that the comment create access handler receives the commented
    // entity as context but no parent comment entity when is called from the
    // comment entity ::access() method.
    $comment->access('create');
    $create_access_context = $state->get('comment_test.create_access.context');
    $this->assertSame($entity->id(), $create_access_context['commented_entity']->id());
    $this->assertArrayNotHasKey('parent_comment', $create_access_context);

    // Check the same for comment field item list ::access() method.
    $field->access('create');
    $create_access_context = $state->get('comment_test.create_access.context');
    $this->assertSame($entity->id(), $create_access_context['commented_entity']->id());
    $this->assertArrayNotHasKey('parent_comment', $create_access_context);

    // Reply to comment.
    $reply = $this->createComment(['pid' => $comment->id()]);

    // Check that the comment create access handler receives the commented
    // entity and the parent comment entity as context.
    $reply->access('create');
    $create_access_context = $state->get('comment_test.create_access.context');
    $this->assertSame($entity->id(), $create_access_context['commented_entity']->id());
    $this->assertSame($comment->id(), $create_access_context['parent_comment']->id());

    // Check the same for comment field item list ::access() method.
    $field->access("reply to {$comment->id()}");
    $create_access_context = $state->get('comment_test.create_access.context');
    $this->assertSame($entity->id(), $create_access_context['commented_entity']->id());
    $this->assertSame($comment->id(), $create_access_context['parent_comment']->id());
  }

}
