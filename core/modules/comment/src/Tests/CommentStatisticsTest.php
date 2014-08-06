<?php

/**
 * @file
 * Contains Drupal\comment\Tests\CommentStatisticsTest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\CommentManagerInterface;
use Drupal\comment\Entity\Comment;

/**
 * Tests comment statistics on nodes.
 *
 * @group comment
 */
class CommentStatisticsTest extends CommentTestBase {

  function setUp() {
    parent::setUp();

    // Create a second user to post comments.
    $this->web_user2 = $this->drupalCreateUser(array(
      'post comments',
      'create article content',
      'edit own comments',
      'post comments',
      'skip comment approval',
      'access comments',
      'access content',
    ));
  }

  /**
   * Tests the node comment statistics.
   */
  function testCommentNodeCommentStatistics() {
    // Set comments to have subject and preview disabled.
    $this->drupalLogin($this->admin_user);
    $this->setCommentPreview(DRUPAL_DISABLED);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(FALSE);
    $this->setCommentSettings('default_mode', CommentManagerInterface::COMMENT_MODE_THREADED, 'Comment paging changed.');
    $this->drupalLogout();

    // Checks the initial values of node comment statistics with no comment.
    $node = node_load($this->node->id());
    $this->assertEqual($node->get('comment')->last_comment_timestamp, $this->node->getCreatedTime(), 'The initial value of node last_comment_timestamp is the node created date.');
    $this->assertEqual($node->get('comment')->last_comment_name, NULL, 'The initial value of node last_comment_name is NULL.');
    $this->assertEqual($node->get('comment')->last_comment_uid, $this->web_user->id(), 'The initial value of node last_comment_uid is the node uid.');
    $this->assertEqual($node->get('comment')->comment_count, 0, 'The initial value of node comment_count is zero.');

    // Post comment #1 as web_user2.
    $this->drupalLogin($this->web_user2);
    $comment_text = $this->randomMachineName();
    $this->postComment($this->node, $comment_text);

    // Checks the new values of node comment statistics with comment #1.
    // The node needs to be reloaded with a node_load_multiple cache reset.
    $node = node_load($this->node->id(), TRUE);
    $this->assertEqual($node->get('comment')->last_comment_name, NULL, 'The value of node last_comment_name is NULL.');
    $this->assertEqual($node->get('comment')->last_comment_uid, $this->web_user2->id(), 'The value of node last_comment_uid is the comment #1 uid.');
    $this->assertEqual($node->get('comment')->comment_count, 1, 'The value of node comment_count is 1.');

    // Prepare for anonymous comment submission (comment approval enabled).
    $this->drupalLogin($this->admin_user);
    user_role_change_permissions(DRUPAL_ANONYMOUS_RID, array(
      'access comments' => TRUE,
      'post comments' => TRUE,
      'skip comment approval' => FALSE,
    ));
    // Ensure that the poster can leave some contact info.
    $this->setCommentAnonymous('1');
    $this->drupalLogout();

    // Post comment #2 as anonymous (comment approval enabled).
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment');
    $anonymous_comment = $this->postComment($this->node, $this->randomMachineName(), '', TRUE);

    // Checks the new values of node comment statistics with comment #2 and
    // ensure they haven't changed since the comment has not been moderated.
    // The node needs to be reloaded with a node_load_multiple cache reset.
    $node = node_load($this->node->id(), TRUE);
    $this->assertEqual($node->get('comment')->last_comment_name, NULL, 'The value of node last_comment_name is still NULL.');
    $this->assertEqual($node->get('comment')->last_comment_uid, $this->web_user2->id(), 'The value of node last_comment_uid is still the comment #1 uid.');
    $this->assertEqual($node->get('comment')->comment_count, 1, 'The value of node comment_count is still 1.');

    // Prepare for anonymous comment submission (no approval required).
    $this->drupalLogin($this->admin_user);
    user_role_change_permissions(DRUPAL_ANONYMOUS_RID, array(
      'access comments' => TRUE,
      'post comments' => TRUE,
      'skip comment approval' => TRUE,
    ));
    $this->drupalLogout();

    // Post comment #3 as anonymous.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment');
    $anonymous_comment = $this->postComment($this->node, $this->randomMachineName(), '', array('name' => $this->randomMachineName()));
    $comment_loaded = Comment::load($anonymous_comment->id());

    // Checks the new values of node comment statistics with comment #3.
    // The node needs to be reloaded with a node_load_multiple cache reset.
    $node = node_load($this->node->id(), TRUE);
    $this->assertEqual($node->get('comment')->last_comment_name, $comment_loaded->getAuthorName(), 'The value of node last_comment_name is the name of the anonymous user.');
    $this->assertEqual($node->get('comment')->last_comment_uid, 0, 'The value of node last_comment_uid is zero.');
    $this->assertEqual($node->get('comment')->comment_count, 2, 'The value of node comment_count is 2.');
  }

}
