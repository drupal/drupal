<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentNodeAccessTest.
 */

namespace Drupal\comment\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests comments with node access.
 *
 * See http://drupal.org/node/886752 -- verify there is no PostgreSQL error when
 * viewing a node with threaded comments (a comment and a reply), if a node
 * access module is in use.
 */
class CommentNodeAccessTest extends CommentTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Comment node access',
      'description' => 'Test comment viewing with node access.',
      'group' => 'Comment',
    );
  }

  function setUp() {
    WebTestBase::setUp('comment', 'search', 'node_access_test');
    node_access_rebuild();

    // Create users and test node.
    $this->admin_user = $this->drupalCreateUser(array('administer content types', 'administer comments', 'administer blocks'));
    $this->web_user = $this->drupalCreateUser(array('access comments', 'post comments', 'create article content', 'edit own comments', 'node test view'));
    $this->node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1, 'uid' => $this->web_user->uid));
  }

  /**
   * Test that threaded comments can be viewed.
   */
  function testThreadedCommentView() {
    $langcode = LANGUAGE_NOT_SPECIFIED;
    // Set comments to have subject required and preview disabled.
    $this->drupalLogin($this->admin_user);
    $this->setCommentPreview(DRUPAL_DISABLED);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(TRUE);
    $this->setCommentSettings('comment_default_mode', COMMENT_MODE_THREADED, t('Comment paging changed.'));
    $this->drupalLogout();

    // Post comment.
    $this->drupalLogin($this->web_user);
    $comment_text = $this->randomName();
    $comment_subject = $this->randomName();
    $comment = $this->postComment($this->node, $comment_text, $comment_subject);
    $comment_loaded = comment_load($comment->id);
    $this->assertTrue($this->commentExists($comment), t('Comment found.'));

    // Check comment display.
    $this->drupalGet('node/' . $this->node->nid . '/' . $comment->id);
    $this->assertText($comment_subject, t('Individual comment subject found.'));
    $this->assertText($comment_text, t('Individual comment body found.'));

    // Reply to comment, creating second comment.
    $this->drupalGet('comment/reply/' . $this->node->nid . '/' . $comment->id);
    $reply_text = $this->randomName();
    $reply_subject = $this->randomName();
    $reply = $this->postComment(NULL, $reply_text, $reply_subject, TRUE);
    $reply_loaded = comment_load($reply->id);
    $this->assertTrue($this->commentExists($reply, TRUE), t('Reply found.'));

    // Go to the node page and verify comment and reply are visible.
    $this->drupalGet('node/' . $this->node->nid);
    $this->assertText($comment_text);
    $this->assertText($comment_subject);
    $this->assertText($reply_text);
    $this->assertText($reply_subject);
  }
}
