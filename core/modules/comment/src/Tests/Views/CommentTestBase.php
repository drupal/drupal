<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\Views\CommentTestBase.
 */

namespace Drupal\comment\Tests\Views;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the argument_comment_user_uid handler.
 */
abstract class CommentTestBase extends ViewTestBase {

  use CommentTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('node', 'comment', 'comment_test_views');

  /**
   * A normal user with permission to post comments (without approval).
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * A second normal user that will author a node for $account to comment on.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account2;

  /**
   * Stores a node posted by the user created as $account.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $nodeUserPosted;

  /**
   * Stores a node posted by the user created as $account2.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $nodeUserCommented;

  /**
   * Stores a comment used by the tests.
   *
   * @var \Drupal\comment\Entity\Comment
   */
  protected $comment;

  protected function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(get_class($this), array('comment_test_views'));

    // Add two users, create a node with the user1 as author and another node
    // with user2 as author. For the second node add a comment from user1.
    $this->account = $this->drupalCreateUser(array('skip comment approval'));
    $this->account2 = $this->drupalCreateUser();
    $this->drupalLogin($this->account);

    $this->drupalCreateContentType(array('type' => 'page', 'name' => t('Basic page')));
    $this->addDefaultCommentField('node', 'page');

    $this->nodeUserPosted = $this->drupalCreateNode();
    $this->nodeUserCommented = $this->drupalCreateNode(array('uid' => $this->account2->id()));

    $comment = array(
      'uid' => $this->loggedInUser->id(),
      'entity_id' => $this->nodeUserCommented->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'subject' => 'How much wood would a woodchuck chuck',
      'cid' => '',
      'pid' => '',
      'mail' => 'someone@example.com',
    );
    $this->comment = entity_create('comment', $comment);
    $this->comment->save();
  }

}
