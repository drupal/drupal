<?php

namespace Drupal\Tests\comment\Functional;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests comment administration and preview access.
 *
 * @group comment
 */
class CommentAccessTest extends BrowserTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'comment',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Node for commenting.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $unpublishedNode;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node_type->save();
    $node_author = $this->drupalCreateUser([
      'create article content',
      'access comments',
    ]);

    $this->drupalLogin($this->drupalCreateUser([
      'edit own comments',
      'skip comment approval',
      'post comments',
      'access comments',
      'access content',
    ]));

    $this->addDefaultCommentField('node', 'article');
    $this->unpublishedNode = $this->createNode([
      'title' => 'This is unpublished',
      'uid' => $node_author->id(),
      'status' => 0,
      'type' => 'article',
    ]);
    $this->unpublishedNode->save();
  }

  /**
   * Tests commenting disabled for access-blocked entities.
   */
  public function testCannotCommentOnEntitiesYouCannotView() {
    $assert = $this->assertSession();

    $comment_url = 'comment/reply/node/' . $this->unpublishedNode->id() . '/comment';

    // Commenting on an unpublished node results in access denied.
    $this->drupalGet($comment_url);
    $assert->statusCodeEquals(403);

    // Publishing the node grants access.
    $this->unpublishedNode->setPublished()->save();
    $this->drupalGet($comment_url);
    $assert->statusCodeEquals(200);
  }

  /**
   * Tests cannot view comment reply form on entities you cannot view.
   */
  public function testCannotViewCommentReplyFormOnEntitiesYouCannotView() {
    $assert = $this->assertSession();

    // Create a comment on an unpublished node.
    $comment = Comment::create([
      'entity_type' => 'node',
      'name' => 'Tony',
      'hostname' => 'magic.example.com',
      'mail' => 'foo@example.com',
      'subject' => 'Comment on unpublished node',
      'entity_id' => $this->unpublishedNode->id(),
      'comment_type' => 'comment',
      'field_name' => 'comment',
      'pid' => 0,
      'uid' => $this->unpublishedNode->getOwnerId(),
      'status' => 1,
    ]);
    $comment->save();

    $comment_url = 'comment/reply/node/' . $this->unpublishedNode->id() . '/comment/' . $comment->id();

    // Replying to a comment on an unpublished node results in access denied.
    $this->drupalGet($comment_url);
    $assert->statusCodeEquals(403);

    // Publishing the node grants access.
    $this->unpublishedNode->setPublished()->save();
    $this->drupalGet($comment_url);
    $assert->statusCodeEquals(200);
  }

}
