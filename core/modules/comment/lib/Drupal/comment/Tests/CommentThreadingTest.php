<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentThreadingTest.
 */

namespace Drupal\comment\Tests;

/**
 * Tests comment threading.
 */
class CommentThreadingTest extends CommentTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Comment Threading',
      'description' => 'Test to make sure the comment number increments properly.',
      'group' => 'Comment',
    );
  }

  /**
   * Tests the comment threading.
   */
  function testCommentThreading() {
    $langcode = LANGUAGE_NOT_SPECIFIED;
    // Set comments to have a subject with preview disabled.
    $this->drupalLogin($this->admin_user);
    $this->setCommentPreview(DRUPAL_DISABLED);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(TRUE);
    $this->setCommentSettings('comment_default_mode', COMMENT_MODE_THREADED, t('Comment paging changed.'));
    $this->drupalLogout();

    // Create a node.
    $this->drupalLogin($this->web_user);
    $this->node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1, 'uid' => $this->web_user->uid));

    // Post comment #1.
    $this->drupalLogin($this->web_user);
    $subject_text = $this->randomName();
    $comment_text = $this->randomName();
    $comment = $this->postComment($this->node, $comment_text, $subject_text, TRUE);
    $comment_loaded = comment_load($comment->id);
    $this->assertTrue($this->commentExists($comment), 'Comment #1. Comment found.');
    $this->assertEqual($comment_loaded->thread, '01/');

    // Reply to comment #1 creating comment #2.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('comment/reply/' . $this->node->nid . '/' . $comment->id);
    $reply = $this->postComment(NULL, $this->randomName(), '', TRUE);
    $reply_loaded = comment_load($reply->id);
    $this->assertTrue($this->commentExists($reply, TRUE), 'Comment #2. Reply found.');
    $this->assertEqual($reply_loaded->thread, '01.00/');

    // Reply to comment #2 creating comment #3.
    $this->drupalGet('comment/reply/' . $this->node->nid . '/' . $reply->id);
    $reply = $this->postComment(NULL, $this->randomName(), $this->randomName(), TRUE);
    $reply_loaded = comment_load($reply->id);
    $this->assertTrue($this->commentExists($reply, TRUE), 'Comment #3. Second reply found.');
    $this->assertEqual($reply_loaded->thread, '01.00.00/');

    // Reply to comment #1 creating comment #4.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('comment/reply/' . $this->node->nid . '/' . $comment->id);
    $reply = $this->postComment(NULL, $this->randomName(), '', TRUE);
    $reply_loaded = comment_load($reply->id);
    $this->assertTrue($this->commentExists($comment), 'Comment #4. Third reply found.');
    $this->assertEqual($reply_loaded->thread, '01.01/');

    // Post comment #2 overall comment #5.
    $this->drupalLogin($this->web_user);
    $subject_text = $this->randomName();
    $comment_text = $this->randomName();
    $comment = $this->postComment($this->node, $comment_text, $subject_text, TRUE);
    $comment_loaded = comment_load($comment->id);
    $this->assertTrue($this->commentExists($comment), 'Comment #5. Second comment found.');
    $this->assertEqual($comment_loaded->thread, '02/');

    // Reply to comment #5 creating comment #6.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('comment/reply/' . $this->node->nid . '/' . $comment->id);
    $reply = $this->postComment(NULL, $this->randomName(), '', TRUE);
    $reply_loaded = comment_load($reply->id);
    $this->assertTrue($this->commentExists($reply, TRUE), 'Comment #6. Reply found.');
    $this->assertEqual($reply_loaded->thread, '02.00/');

    // Reply to comment #6 creating comment #7.
    $this->drupalGet('comment/reply/' . $this->node->nid . '/' . $reply->id);
    $reply = $this->postComment(NULL, $this->randomName(), $this->randomName(), TRUE);
    $reply_loaded = comment_load($reply->id);
    $this->assertTrue($this->commentExists($reply, TRUE), 'Comment #7. Second reply found.');
    $this->assertEqual($reply_loaded->thread, '02.00.00/');

    // Reply to comment #5 creating comment #8.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('comment/reply/' . $this->node->nid . '/' . $comment->id);
    $reply = $this->postComment(NULL, $this->randomName(), '', TRUE);
    $reply_loaded = comment_load($reply->id);
    $this->assertTrue($this->commentExists($comment), 'Comment #8. Third reply found.');
    $this->assertEqual($reply_loaded->thread, '02.01/');
  }
}
