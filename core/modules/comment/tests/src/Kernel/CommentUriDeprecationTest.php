<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\Entity\CommentType;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Performs kernel tests on the deprecation of the comment_uri method.
 */
#[Group('comment')]
#[RunTestsInSeparateProcesses]
class CommentUriDeprecationTest extends EntityKernelTestBase {
  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment', 'node'];

  /**
   * A user to add comments.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $commentUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installConfig(['comment']);

    // Create a page node type.
    $this->entityTypeManager->getStorage('node_type')->create([
      'type' => 'page',
      'name' => 'page',
    ])->save();
    // Create a comment type.
    CommentType::create([
      'id' => 'comment',
      'label' => 'Default comments',
      'description' => 'Default comment field',
      'target_entity_type_id' => 'node',
    ])->save();

    // Add comment field to the page content type.
    $this->addDefaultCommentField('node', 'page', 'comment');
    // Create user to add comments.
    $this->commentUser = $this->drupalCreateUser([
      'access comments',
      'post comments',
      'create page content',
      'edit own comments',
      'skip comment approval',
      'access content',
    ]);

  }

  /**
   * Tests the deprecation of comment_uri() method.
   */
  #[IgnoreDeprecations]
  public function testCommentUriMethod(): void {

    // Create a node with a comment and make it unpublished.
    $node = $this->entityTypeManager->getStorage('node')->create([
      'type' => 'page',
      'title' => 'test 1',
      'promote' => 1,
      'status' => 0,
      'uid' => $this->commentUser->id(),
    ]);
    $node->save();
    $comment = $this->entityTypeManager->getStorage('comment')->create([
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'comment_body' => $this->randomMachineName(),
    ]);
    $comment->save();

    $comment_uri = comment_uri($comment);
    $this->expectDeprecation('comment_uri() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Use \Drupal\comment\Entity\Comment::permalink() instead. See https://www.drupal.org/node/3384294');

    $this->assertEquals('/comment/1#comment-1', $comment_uri->toString());

  }

}
