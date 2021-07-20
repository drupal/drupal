<?php

declare(strict_types = 1);

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\Controller\CommentController;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\comment\Traits\CommentCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Tests the comment controller.
 *
 * @group comment
 * @coversDefaultClass \Drupal\comment\Controller\CommentController
 */
class CommentControllerTest extends KernelTestBase {

  use CommentCreationTrait;
  use CommentTestTrait;
  use NodeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'field',
    'filter',
    'node',
    'system',
    'text',
    'user',
  ];

  /**
   * The tested comment controller.
   *
   * @var \Drupal\comment\Controller\CommentController
   */
  protected $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['comment', 'filter', 'user']);
    $this->installEntitySchema('comment');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('system', ['sequences']);

    NodeType::create(['type' => 'page'])->save();
    $this->addDefaultCommentField('node', 'page');
    // Create a comment controller instance.
    $this->controller = $this->container->get('class_resolver')->getInstanceFromDefinition(CommentController::class);
  }

  /**
   * Tests the reply form access callback.
   *
   * @param array $permissions
   *   User permissions.
   * @param bool $expectation
   *   The access expectation.
   *
   * @covers ::replyFormAccess
   * @dataProvider providerTestReplyFormAccess
   */
  public function testReplyFormAccess(array $permissions, bool $expectation): void {
    // Create and set as current a non-UID1 user.
    $this->setCurrentUser($this->createUser($permissions, NULL, FALSE, ['uid' => 2]));

    // Create an unpublished host entity.
    $node = $this->createNode(['status' => FALSE]);
    $node->save();

    // Check that it's forbidden to comment on a non-accessible entity.
    $this->assertTrue($this->controller->replyFormAccess($node, 'comment')->isNeutral());

    // Publish the node.
    $node->setPublished()->save();
    // Node view access is statically cached. Explicitly clear the cache.
    $this->container->get('entity_type.manager')->getAccessControlHandler('node')->resetCache();

    // Check that only users granted with proper permissions are able comment on
    // an accessible entity.
    $this->assertEquals($expectation, $this->controller->replyFormAccess($node, 'comment')->isAllowed());

    // Close commenting on this node.
    $node->get('comment')->status = CommentItemInterface::CLOSED;

    // Check that is not possible to comment when comments are closed.
    $this->assertTrue($this->controller->replyFormAccess($node, 'comment')->isNeutral());

    // Reopen commenting on this node.
    $node->get('comment')->status = CommentItemInterface::OPEN;

    // Cannot reply to a non-existing comment.
    $non_existing_comment_id = (int) $this->container->get('database')->query("SELECT MAX(cid) FROM {comment}")->fetchField() + 1;
    $access_result = $this->controller->replyFormAccess($node, 'comment', $non_existing_comment_id);
    $this->assertTrue($access_result->isForbidden());
    $this->assertSame('Cannot reply to a non-existing comment', $access_result->getReason());

    // Create a comment to test replies.
    $comment = $this->createComment([
      'entity_type' => 'node',
      'entity_id' => $node->id(),
    ]);

    // Check that users granted with proper permissions can reply to a comment.
    $this->assertEquals($expectation, $this->controller->replyFormAccess($node, 'comment', $comment->id())->isAllowed());

    // Unpublish the comment.
    $comment->setUnpublished()->save();

    // Check that users cannot reply to an unpublished comment.
    $this->assertTrue($this->controller->replyFormAccess($node, 'comment', $comment->id())->isNeutral());

    // Publish the parent comment but move it to a different node to a different node.
    $other_node = $this->createNode(['type' => 'page']);
    $comment->setPublished()->set('entity_id', $other_node->id())->save();

    // Check that users cannot reply to a comment from other entity.
    $this->assertTrue($this->controller->replyFormAccess($node, 'comment', $comment->id())->isNeutral());
  }

  /**
   * Provides testing cases for ::testReplyFormAccess()
   *
   * @return array[]
   *   Testing scenarios.
   *
   * @see self::testReplyFormAccess()
   */
  public function providerTestReplyFormAccess(): array {
    return [
      'commenter' => [
        ['access content', 'access comments', 'post comments'], TRUE,
      ],
      'only view comments' => [
        ['access content', 'access comments'], FALSE,
      ],
      'comments forbidden' => [
        ['access content'], FALSE,
      ],
    ];
  }

  /**
   * Tests that the controller returns 404 on a nonexistent comment field.
   *
   * @covers ::replyFormAccess
   */
  public function testReplyFormAccessWrongField(): void {
    $node = $this->createNode(['type' => 'page']);
    // Check that the controller returns 404 on a nonexistent comment field.
    $this->expectException(NotFoundHttpException::class);
    $this->controller->replyFormAccess($node, 'non_existent_comment_field');
  }

}
