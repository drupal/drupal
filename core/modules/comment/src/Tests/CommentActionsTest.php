<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentActionsTest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\Entity\Comment;

/**
 * Tests actions provided by the Comment module.
 *
 * @group comment
 */
class CommentActionsTest extends CommentTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('dblog', 'action');

  /**
   * Tests comment publish and unpublish actions.
   */
  function testCommentPublishUnpublishActions() {
    $this->drupalLogin($this->web_user);
    $comment_text = $this->randomMachineName();
    $subject = $this->randomMachineName();
    $comment = $this->postComment($this->node, $comment_text, $subject);

    // Unpublish a comment.
    $action = entity_load('action', 'comment_unpublish_action');
    $action->execute(array($comment));
    $this->assertTrue($comment->isPublished() === FALSE, 'Comment was unpublished');

    // Publish a comment.
    $action = entity_load('action', 'comment_publish_action');
    $action->execute(array($comment));
    $this->assertTrue($comment->isPublished() === TRUE, 'Comment was published');
  }

  /**
   * Tests the unpublish comment by keyword action.
   */
  function testCommentUnpublishByKeyword() {
    $this->drupalLogin($this->admin_user);
    $keyword_1 = $this->randomMachineName();
    $keyword_2 = $this->randomMachineName();
    $action = entity_create('action', array(
      'id' => 'comment_unpublish_by_keyword_action',
      'label' => $this->randomMachineName(),
      'type' => 'comment',
      'configuration' => array(
        'keywords' => array($keyword_1, $keyword_2),
      ),
      'plugin' => 'comment_unpublish_by_keyword_action',
    ));
    $action->save();

    $comment = $this->postComment($this->node, $keyword_2, $this->randomMachineName());

    // Load the full comment so that status is available.
    $comment = Comment::load($comment->id());

    $this->assertTrue($comment->isPublished() === TRUE, 'The comment status was set to published.');

    $action->execute(array($comment));
    $this->assertTrue($comment->isPublished() === FALSE, 'The comment status was set to not published.');
  }

}
