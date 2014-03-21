<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\Views\CommentTestBase.
 */

namespace Drupal\comment\Tests\Views;

use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the argument_comment_user_uid handler.
 */
abstract class CommentTestBase extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'comment', 'comment_test_views');

  /**
   * Stores a comment used by the tests.
   *
   * @var \Drupal\comment\Entity\Comment
   */
  public $comment;

  function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(get_class($this), array('comment_test_views'));

    // Add two users, create a node with the user1 as author and another node
    // with user2 as author. For the second node add a comment from user1.
    $this->account = $this->drupalCreateUser(array('skip comment approval'));
    $this->account2 = $this->drupalCreateUser();
    $this->drupalLogin($this->account);

    $this->drupalCreateContentType(array('type' => 'page', 'name' => t('Basic page')));
    $this->container->get('comment.manager')->addDefaultField('node', 'page');

    $this->node_user_posted = $this->drupalCreateNode();
    $this->node_user_commented = $this->drupalCreateNode(array('uid' => $this->account2->id()));

    $comment = array(
      'uid' => $this->loggedInUser->id(),
      'entity_id' => $this->node_user_commented->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'cid' => '',
      'pid' => '',
      'node_type' => '',
    );
    $this->comment = entity_create('comment', $comment);
    $this->comment->save();
  }

}
