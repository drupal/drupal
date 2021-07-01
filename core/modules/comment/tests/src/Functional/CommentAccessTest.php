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
      'administer comments',
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
   * Tests to ensure that reply form is accessible on unpublished comment
   * for comment administrators.
   */
  public function testUnpublishedCommentReplyForCommentAdministrators() {
    $assert = $this->assertSession();

    // Publish the node.
    $this->unpublishedNode->setPublished()->save();
    $node_id = $this->unpublishedNode->id();

    // Create a comment on an published node.
    $comment = Comment::create([
      'entity_type' => 'node',
      'name' => 'Tony',
      'hostname' => 'magic.example.com',
      'mail' => 'foo@example.com',
      'subject' => 'Unpublished comment on published node',
      'entity_id' => $node_id,
      'comment_type' => 'comment',
      'field_name' => 'comment',
      'pid' => 0,
      'uid' => $this->unpublishedNode->getOwnerId(),
      'status' => 0,
    ]);
    $comment->save();

    $comment_url = 'comment/reply/node/' . $node_id . '/comment/' . $comment->id();

    // Replying to an unpublished node as a user who has "administer comment"
    // permissions.
    $this->drupalGet($comment_url);
    $assert->statusCodeEquals(200);

    // Submit the comment and ensure that comment reply remains unpublished.
    $edit = [
      'subject[0][value]' => 'Comment reply subject',
      'comment_body[0][value]' => 'Comment body',
    ];
    $this->submitForm($edit, 'Save');
    $reply_cid = $this->getUnapprovedComment('Comment reply subject');
    $reply = Comment::load($reply_cid);
    $this->assertEquals(0, $reply->isPublished());

    // Logout and visit as an anonymous user.
    $this->drupalLogout();
    $this->drupalGet($comment_url);
    $assert->statusCodeEquals(403);
  }

}
