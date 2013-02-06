<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentActionsTest.
 */

namespace Drupal\comment\Tests;

/**
 * Tests actions provided by the Comment module.
 */
class CommentActionsTest extends CommentTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('dblog', 'action');

  public static function getInfo() {
    return array(
      'name' => 'Comment actions',
      'description' => 'Test actions provided by the comment module.',
      'group' => 'Comment',
    );
  }

  /**
   * Tests comment publish and unpublish actions.
   */
  function testCommentPublishUnpublishActions() {
    $this->drupalLogin($this->web_user);
    $comment_text = $this->randomName();
    $subject = $this->randomName();
    $comment = $this->postComment($this->node, $comment_text, $subject);

    // Unpublish a comment (direct form: doesn't actually save the comment).
    comment_unpublish_action($comment);
    $this->assertEqual($comment->status->value, COMMENT_NOT_PUBLISHED, 'Comment was unpublished');
    $this->assertWatchdogMessage('Unpublished comment %subject.', array('%subject' => $subject), 'Found watchdog message');
    $this->clearWatchdog();

    // Unpublish a comment (indirect form: modify the comment in the database).
    comment_unpublish_action(NULL, array('cid' => $comment->id()));
    $this->assertEqual(comment_load($comment->id())->status->value, COMMENT_NOT_PUBLISHED, 'Comment was unpublished');
    $this->assertWatchdogMessage('Unpublished comment %subject.', array('%subject' => $subject), 'Found watchdog message');

    // Publish a comment (direct form: doesn't actually save the comment).
    comment_publish_action($comment);
    $this->assertEqual($comment->status->value, COMMENT_PUBLISHED, 'Comment was published');
    $this->assertWatchdogMessage('Published comment %subject.', array('%subject' => $subject), 'Found watchdog message');
    $this->clearWatchdog();

    // Publish a comment (indirect form: modify the comment in the database).
    comment_publish_action(NULL, array('cid' => $comment->id()));
    $this->assertEqual(comment_load($comment->id())->status->value, COMMENT_PUBLISHED, 'Comment was published');
    $this->assertWatchdogMessage('Published comment %subject.', array('%subject' => $subject), 'Found watchdog message');
    $this->clearWatchdog();
  }

  /**
   * Tests the unpublish comment by keyword action.
   */
  function testCommentUnpublishByKeyword() {
    $this->drupalLogin($this->admin_user);
    $keyword_1 = $this->randomName();
    $keyword_2 = $this->randomName();
    $aid = action_save('comment_unpublish_by_keyword_action', 'comment', array('keywords' => array($keyword_1, $keyword_2)), $this->randomName());

    $this->assertTrue(action_load($aid), 'The action could be loaded.');

    $comment = $this->postComment($this->node, $keyword_2, $this->randomName());

    // Load the full comment so that status is available.
    $comment = comment_load($comment->id());

    $this->assertTrue($comment->status->value == COMMENT_PUBLISHED, 'The comment status was set to published.');

    actions_do($aid, $comment, array());
    $this->assertTrue($comment->status->value == COMMENT_NOT_PUBLISHED, 'The comment status was set to not published.');
  }

  /**
   * Verifies that a watchdog message has been entered.
   *
   * @param $watchdog_message
   *   The watchdog message.
   * @param $variables
   *   The array of variables passed to watchdog().
   * @param $message
   *   The assertion message.
   */
  function assertWatchdogMessage($watchdog_message, $variables, $message) {
    $status = (bool) db_query_range("SELECT 1 FROM {watchdog} WHERE message = :message AND variables = :variables", 0, 1, array(':message' => $watchdog_message, ':variables' => serialize($variables)))->fetchField();
    return $this->assert($status, format_string('@message', array('@message'=> $message)));
  }

  /**
   * Clears watchdog.
   */
  function clearWatchdog() {
    db_truncate('watchdog')->execute();
  }
}
