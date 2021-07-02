<?php

namespace Drupal\Tests\comment\Functional;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;

/**
 * Tests comment administration and preview access.
 *
 * @group comment
 */
class CommentAccessTest extends CommentTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'comment',
    'comment_test',
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

  /**
   * Tests that direct access to comment approval URL returns proper message.
   */
  public function testCommentApprovalAccess() {
    $this->drupalLogin($this->adminUser);

    // Create a comment on an unpublished node.
    $comment = Comment::create([
      'entity_type' => 'node',
      'name' => 'Tony',
      'hostname' => 'magic.example.com',
      'mail' => 'foo@example.com',
      'subject' => 'Comment on node',
      'entity_id' => $this->node->id(),
      'comment_type' => 'comment',
      'field_name' => 'comment',
      'pid' => 0,
      'uid' => $this->node->getOwnerId(),
      'status' => 0,
    ]);
    $comment->save();

    // Here we are calling comment_test module's url. This url is calling the
    // same controller. It has CSRF token disabled. Token is intentionally kept
    // disabled to prevent validation. By disabling CSRF token we can directly
    // call the controller method and test the messages.
    $approval_url = 'comment-test/' . $comment->id() . '/approve';
    $this->drupalGet($approval_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Comment approved.');

    // Send the request again to comment approval url.
    $approval_url = 'comment-test/' . $comment->id() . '/approve';
    $this->drupalGet($approval_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The comment is already published.');
  }

}
