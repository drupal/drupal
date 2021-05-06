<?php

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\CancellationHandlerInterface;
use Drupal\user\UserInterface;

/**
 * Tests how comments react to user cancellation.
 *
 * @group comment
 */
class UserCancellationTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'field',
    'node',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig('node');

    CommentType::create([
      'id' => 'test',
      'target_entity_type_id' => 'node',
    ])->save();

    $this->createContentType(['type' => 'page']);

    FieldStorageConfig::create([
      'type' => 'comment',
      'entity_type' => 'node',
      'field_name' => 'comments',
    ])->save();

    FieldConfig::create([
      'field_name' => 'comments',
      'entity_type' => 'node',
      'bundle' => 'page',
    ])->save();

    $this->container->get('config.factory')
      ->getEditable('user.settings')
      ->set('anonymous', 'Mysterious Stranger')
      ->save();
  }

  /**
   * Tests how comment entities handle user cancellation.
   */
  public function testUserCancellation(): void {
    // With the BLOCK_UNPUBLISH method, the comment should still be associated
    // with the cancelled account, but be unpublished.
    $user = $this->createUser();
    $comment = $this->createComment($user);
    user_cancel([], $user->id(), CancellationHandlerInterface::METHOD_BLOCK_UNPUBLISH);
    $comment = Comment::load($comment->id());
    $this->assertSame($user->id(), $comment->getOwnerId());
    $this->assertFalse($comment->isPublished());

    // With the REASSIGN method, the comment should still be published, but
    // associated with the anonymous user.
    $user = $this->createUser();
    $comment = $this->createComment($user);
    user_cancel([], $user->id(), CancellationHandlerInterface::METHOD_REASSIGN);
    $comment = Comment::load($comment->id());
    $this->assertTrue($comment->getOwner()->isAnonymous());
    $this->assertSame('Mysterious Stranger', $comment->getAuthorName());
    $this->assertTrue($comment->isPublished());
  }

  /**
   * Creates a published comment associated with a user account.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account which should own the comment.
   *
   * @return \Drupal\comment\Entity\Comment
   *   The saved comment entity.
   */
  protected function createComment(UserInterface $user): Comment {
    $comment = Comment::create([
      'comment_type' => 'test',
      'entity_type' => 'node',
      'field_name' => 'comments',
    ]);
    $comment->setOwner($user)->setPublished()->save();
    $this->assertSame($user->id(), $comment->getOwnerId());
    $this->assertTrue($comment->isPublished());

    return $comment;
  }

}
