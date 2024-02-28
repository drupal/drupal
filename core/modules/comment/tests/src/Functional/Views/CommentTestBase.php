<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Functional\Views;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\comment\Entity\Comment;

/**
 * Provides setup and helper methods for comment views tests.
 */
abstract class CommentTestBase extends ViewTestBase {

  use CommentTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['node', 'comment', 'comment_test_views'];

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

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['comment_test_views']): void {
    parent::setUp($import_test_views, $modules);

    // Add two users, create a node with the user1 as author and another node
    // with user2 as author. For the second node add a comment from user1.
    $this->account = $this->drupalCreateUser(['skip comment approval']);
    $this->account2 = $this->drupalCreateUser();
    $this->drupalLogin($this->account);

    $this->drupalCreateContentType(['type' => 'page', 'name' => t('Basic page')]);
    $this->addDefaultCommentField('node', 'page');

    $this->nodeUserPosted = $this->drupalCreateNode();
    $this->nodeUserCommented = $this->drupalCreateNode(['uid' => $this->account2->id()]);

    $comment = [
      'uid' => $this->loggedInUser->id(),
      'entity_id' => $this->nodeUserCommented->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'subject' => 'How much wood would a woodchuck chuck',
      'cid' => '',
      'pid' => '',
      'mail' => 'someone@example.com',
    ];
    $this->comment = Comment::create($comment);
    $this->comment->save();
  }

}
