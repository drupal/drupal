<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentActionsTest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\CommentInterface;

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
    $keyword_1 = $this->randomName();
    $keyword_2 = $this->randomName();
    $action = entity_create('action', array(
      'id' => 'comment_unpublish_by_keyword_action',
      'label' => $this->randomName(),
      'type' => 'comment',
      'configuration' => array(
        'keywords' => array($keyword_1, $keyword_2),
      ),
      'plugin' => 'comment_unpublish_by_keyword_action',
    ));
    $action->save();

    $comment = $this->postComment($this->node, $keyword_2, $this->randomName());

    // Load the full comment so that status is available.
    $comment = comment_load($comment->id());

    $this->assertTrue($comment->isPublished() === TRUE, 'The comment status was set to published.');

    $action->execute(array($comment));
    $this->assertTrue($comment->isPublished() === FALSE, 'The comment status was set to not published.');
  }

}
