<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Comment\CommentTestBase.
 */

namespace Drupal\views\Tests\Comment;

use Drupal\views\Tests\ViewTestBase;

/**
 * Tests the argument_comment_user_uid handler.
 */
abstract class CommentTestBase extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment');

  function setUp() {
    parent::setUp();

    // Add two users, create a node with the user1 as author and another node
    // with user2 as author. For the second node add a comment from user1.
    $this->account = $this->drupalCreateUser();
    $this->account2 = $this->drupalCreateUser();
    $this->drupalLogin($this->account);

    comment_add_default_comment_field('node', 'page');

    $this->node_user_posted = $this->drupalCreateNode(array(
      'comment' => array(
        LANGUAGE_NOT_SPECIFIED => array(array('comment' => COMMENT_OPEN))
      ),
    ));
    $this->node_user_commented = $this->drupalCreateNode(array(
      'uid' => $this->account2->uid,
      'comment' => array(
        LANGUAGE_NOT_SPECIFIED => array(array('comment' => COMMENT_OPEN))
      ),
    ));

    $comment = array(
      'uid' => $this->loggedInUser->uid,
      'entity_id' => $this->node_user_commented->nid,
      'entity_type' => 'node',
      'field_name' => 'comment',
      'cid' => '',
      'pid' => '',
    );
    entity_create('comment', $comment)->save();
  }

}
