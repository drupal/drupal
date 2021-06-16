<?php

namespace Drupal\Tests\comment\Functional;

use Drupal\comment\CommentManagerInterface;

/**
 * Tests comments with node access.
 *
 * Verifies there is no PostgreSQL error when viewing a node with threaded
 * comments (a comment and a reply), if a node access module is in use.
 *
 * @group comment
 */
class CommentNodeAccessTest extends CommentTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['node_access_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  protected function setUp(): void {
    parent::setUp();

    node_access_rebuild();

    // Re-create user.
    $this->webUser = $this->drupalCreateUser([
      'access comments',
      'post comments',
      'create article content',
      'edit own comments',
      'node test view',
      'skip comment approval',
    ]);

    // Set the author of the created node to the web_user uid.
    $this->node->setOwnerId($this->webUser->id())->save();
  }

  /**
   * Tests that threaded comments can be viewed.
   */
  public function testThreadedCommentView() {
    // Set comments to have subject required and preview disabled.
    $this->drupalLogin($this->adminUser);
    $this->setCommentPreview(DRUPAL_DISABLED);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(TRUE);
    $this->setCommentSettings('default_mode', CommentManagerInterface::COMMENT_MODE_THREADED, 'Comment paging changed.');
    $this->drupalLogout();

    // Post comment.
    $this->drupalLogin($this->webUser);
    $comment_text = $this->randomMachineName();
    $comment_subject = $this->randomMachineName();
    $comment = $this->postComment($this->node, $comment_text, $comment_subject);
    $this->assertTrue($this->commentExists($comment), 'Comment found.');

    // Check comment display.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->pageTextContains($comment_subject);
    $this->assertSession()->pageTextContains($comment_text);

    // Reply to comment, creating second comment.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment->id());
    $reply_text = $this->randomMachineName();
    $reply_subject = $this->randomMachineName();
    $reply = $this->postComment(NULL, $reply_text, $reply_subject, TRUE);
    $this->assertTrue($this->commentExists($reply, TRUE), 'Reply found.');

    // Go to the node page and verify comment and reply are visible.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->pageTextContains($comment_text);
    $this->assertSession()->pageTextContains($comment_subject);
    $this->assertSession()->pageTextContains($reply_text);
    $this->assertSession()->pageTextContains($reply_subject);
  }

}
