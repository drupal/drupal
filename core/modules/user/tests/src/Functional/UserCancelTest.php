<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Functional;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Ensure that account cancellation methods work as expected.
 *
 * @group user
 * @group #slow
 */
class UserCancelTest extends BrowserTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'comment'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
  }

  /**
   * Attempt to cancel account without permission.
   */
  public function testUserCancelWithoutPermission(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $this->config('user.settings')->set('cancel_method', 'user_cancel_reassign')->save();
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');

    // Create a user.
    $account = $this->drupalCreateUser([]);
    $this->drupalLogin($account);
    // Load a real user object.
    $user_storage->resetCache([$account->id()]);
    $account = $user_storage->load($account->id());

    // Create a node.
    $node = $this->drupalCreateNode(['uid' => $account->id()]);

    // Attempt to cancel account.
    $this->drupalGet('user/' . $account->id() . '/edit');
    $this->assertSession()->pageTextNotContains("Cancel account");

    // Attempt bogus account cancellation request confirmation.
    $timestamp = $account->getLastLoginTime();
    $this->drupalGet("user/" . $account->id() . "/cancel/confirm/$timestamp/" . user_pass_rehash($account, $timestamp));
    $this->assertSession()->statusCodeEquals(403);
    $user_storage->resetCache([$account->id()]);
    $account = $user_storage->load($account->id());
    $this->assertTrue($account->isActive(), 'User account was not canceled.');

    // Confirm user's content has not been altered.
    $node_storage->resetCache([$node->id()]);
    $test_node = $node_storage->load($node->id());
    $this->assertEquals($account->id(), $test_node->getOwnerId(), 'Node of the user has not been altered.');
    $this->assertTrue($test_node->isPublished());
  }

  /**
   * Tests ability to change the permission for canceling users.
   */
  public function testUserCancelChangePermission(): void {
    \Drupal::service('module_installer')->install(['user_form_test']);
    $this->config('user.settings')->set('cancel_method', 'user_cancel_reassign')->save();

    // Create a regular user.
    $account = $this->drupalCreateUser([]);

    $admin_user = $this->drupalCreateUser(['cancel other accounts']);
    $this->drupalLogin($admin_user);

    // Delete regular user.
    $this->drupalGet('user_form_test_cancel/' . $account->id());
    $this->submitForm([], 'Confirm');

    // Confirm deletion.
    $this->assertSession()->pageTextContains("Account {$account->getAccountName()} has been deleted.");
    $this->assertNull(User::load($account->id()), 'User is not found in the database.');
  }

  /**
   * Tests that user account for uid 1 cannot be cancelled.
   *
   * This should never be possible, or the site owner would become unable to
   * administer the site.
   */
  public function testUserCancelUid1(): void {
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');

    \Drupal::service('module_installer')->install(['views']);

    // Try to cancel uid 1's account with a different user.
    $admin_user = $this->drupalCreateUser(['administer users']);
    $this->drupalLogin($admin_user);
    $edit = [
      'action' => 'user_cancel_user_action',
      'user_bulk_form[0]' => TRUE,
    ];
    $this->drupalGet('admin/people');
    $this->submitForm($edit, 'Apply to selected items');

    // Verify that uid 1's account was not cancelled.
    $user_storage->resetCache([1]);
    $user1 = $user_storage->load(1);
    $this->assertTrue($user1->isActive(), 'User #1 still exists and is not blocked.');
  }

  /**
   * Attempt invalid account cancellations.
   */
  public function testUserCancelInvalid(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $this->config('user.settings')->set('cancel_method', 'user_cancel_reassign')->save();
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');

    // Create a user.
    $account = $this->drupalCreateUser(['cancel account']);
    $this->drupalLogin($account);
    // Load a real user object.
    $user_storage->resetCache([$account->id()]);
    $account = $user_storage->load($account->id());

    // Create a node.
    $node = $this->drupalCreateNode(['uid' => $account->id()]);

    // Attempt to cancel account.
    $this->drupalGet('user/' . $account->id() . '/cancel');
    $timestamp = time();
    $this->submitForm([], 'Confirm');
    $this->assertSession()->pageTextContains('A confirmation request to cancel your account has been sent to your email address.');

    // Attempt bogus account cancellation request confirmation.
    $bogus_timestamp = $timestamp + 60;
    $this->drupalGet("user/" . $account->id() . "/cancel/confirm/$bogus_timestamp/" . user_pass_rehash($account, $bogus_timestamp));
    $this->assertSession()->pageTextContains('You have tried to use an account cancellation link that has expired. Request a new one using the form below.');
    $user_storage->resetCache([$account->id()]);
    $account = $user_storage->load($account->id());
    $this->assertTrue($account->isActive(), 'User account was not canceled.');

    // Attempt expired account cancellation request confirmation.
    $bogus_timestamp = $timestamp - 86400 - 60;
    $this->drupalGet("user/" . $account->id() . "/cancel/confirm/$bogus_timestamp/" . user_pass_rehash($account, $bogus_timestamp));
    $this->assertSession()->pageTextContains('You have tried to use an account cancellation link that has expired. Request a new one using the form below.');
    $user_storage->resetCache([$account->id()]);
    $account = $user_storage->load($account->id());
    $this->assertTrue($account->isActive(), 'User account was not canceled.');

    // Confirm user's content has not been altered.
    $node_storage->resetCache([$node->id()]);
    $test_node = $node_storage->load($node->id());
    $this->assertEquals($account->id(), $test_node->getOwnerId(), 'Node of the user has not been altered.');
    $this->assertTrue($test_node->isPublished());
  }

  /**
   * Disable account and keep all content.
   */
  public function testUserBlock(): void {
    $this->config('user.settings')->set('cancel_method', 'user_cancel_block')->save();
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');

    // Create a user.
    $web_user = $this->drupalCreateUser(['cancel account']);
    $this->drupalLogin($web_user);

    // Load a real user object.
    $user_storage->resetCache([$web_user->id()]);
    $account = $user_storage->load($web_user->id());

    // Attempt to cancel account.
    $this->drupalGet('user/' . $account->id() . '/cancel');
    $this->assertSession()->pageTextContains('Are you sure you want to cancel your account?');
    $this->assertSession()->pageTextContains('Your account will be blocked and you will no longer be able to log in. All of your content will remain attributed to your username.');
    $this->assertSession()->pageTextNotContains('Cancellation method');

    // Confirm account cancellation.
    $timestamp = time();

    $this->submitForm([], 'Confirm');
    $this->assertSession()->pageTextContains('A confirmation request to cancel your account has been sent to your email address.');

    // Confirm account cancellation request.
    $this->drupalGet("user/" . $account->id() . "/cancel/confirm/$timestamp/" . user_pass_rehash($account, $timestamp));
    $user_storage->resetCache([$account->id()]);
    $account = $user_storage->load($account->id());
    $this->assertTrue($account->isBlocked(), 'User has been blocked.');

    // Confirm that the confirmation message made it through to the end user.
    $this->assertSession()->pageTextContains("Account {$account->getAccountName()} has been disabled.");
  }

  /**
   * Disable account and unpublish all content.
   */
  public function testUserBlockUnpublish(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $this->config('user.settings')->set('cancel_method', 'user_cancel_block_unpublish')->save();
    // Create comment field on page.
    $this->addDefaultCommentField('node', 'page');
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');

    // Create a user.
    $account = $this->drupalCreateUser(['cancel account']);
    $this->drupalLogin($account);
    // Load a real user object.
    $user_storage->resetCache([$account->id()]);
    $account = $user_storage->load($account->id());

    // Create a node with two revisions.
    $node = $this->drupalCreateNode(['uid' => $account->id()]);
    $settings = get_object_vars($node);
    $settings['revision'] = 1;
    $node = $this->drupalCreateNode($settings);

    // Add a comment to the page.
    $comment_subject = $this->randomMachineName(8);
    $comment_body = $this->randomMachineName(8);
    $comment = Comment::create([
      'subject' => $comment_subject,
      'comment_body' => $comment_body,
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'status' => CommentInterface::PUBLISHED,
      'uid' => $account->id(),
    ]);
    $comment->save();

    // Attempt to cancel account.
    $this->drupalGet('user/' . $account->id() . '/cancel');
    $this->assertSession()->pageTextContains('Are you sure you want to cancel your account?');
    $this->assertSession()->pageTextContains('Your account will be blocked and you will no longer be able to log in. All of your content will be hidden from everyone but administrators.');

    // Confirm account cancellation.
    $timestamp = time();
    $this->submitForm([], 'Confirm');
    $this->assertSession()->pageTextContains('A confirmation request to cancel your account has been sent to your email address.');

    // Confirm account cancellation request.
    $this->drupalGet("user/" . $account->id() . "/cancel/confirm/$timestamp/" . user_pass_rehash($account, $timestamp));
    // Confirm that the user was redirected to the front page.
    $this->assertSession()->addressEquals('');
    $this->assertSession()->statusCodeEquals(200);
    // Confirm that the confirmation message made it through to the end user.
    $this->assertSession()->pageTextContains("Account {$account->getAccountName()} has been disabled.");

    $user_storage->resetCache([$account->id()]);
    $account = $user_storage->load($account->id());
    $this->assertTrue($account->isBlocked(), 'User has been blocked.');

    // Confirm user's content has been unpublished.
    $node_storage->resetCache([$node->id()]);
    $test_node = $node_storage->load($node->id());
    $this->assertFalse($test_node->isPublished(), 'Node of the user has been unpublished.');
    $test_node = $node_storage->loadRevision($node->getRevisionId());
    $this->assertFalse($test_node->isPublished(), 'Node revision of the user has been unpublished.');

    $storage = \Drupal::entityTypeManager()->getStorage('comment');
    $storage->resetCache([$comment->id()]);
    $comment = $storage->load($comment->id());
    $this->assertFalse($comment->isPublished(), 'Comment of the user has been unpublished.');
  }

  /**
   * Tests nodes are unpublished even if inaccessible to cancelling user.
   */
  public function testUserBlockUnpublishNodeAccess(): void {
    \Drupal::service('module_installer')->install(['node_access_test', 'user_form_test']);

    // Setup node access
    node_access_rebuild();
    node_access_test_add_field(NodeType::load('page'));
    \Drupal::state()->set('node_access_test.private', TRUE);

    $this->config('user.settings')->set('cancel_method', 'user_cancel_block_unpublish')->save();

    // Create a user.
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');
    $account = $this->drupalCreateUser(['cancel account']);
    // Load a real user object.
    $user_storage->resetCache([$account->id()]);
    $account = $user_storage->load($account->id());

    // Create a published private node.
    $node = $this->drupalCreateNode([
      'uid' => $account->id(),
      'type' => 'page',
      'status' => 1,
      'private' => TRUE,
    ]);

    // Cancel node author.
    $admin_user = $this->drupalCreateUser(['cancel other accounts']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('user_form_test_cancel/' . $account->id());
    $this->submitForm([], 'Confirm');

    // Confirm node has been unpublished, even though the admin user
    // does not have permission to access it.
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $node_storage->resetCache([$node->id()]);
    $test_node = $node_storage->load($node->id());
    $this->assertFalse($test_node->isPublished(), 'Node of the user has been unpublished.');
  }

  /**
   * Delete account and anonymize all content.
   */
  public function testUserAnonymize(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $this->config('user.settings')->set('cancel_method', 'user_cancel_reassign')->save();
    // Create comment field on page.
    $this->addDefaultCommentField('node', 'page');
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');

    // Create a user.
    $account = $this->drupalCreateUser(['cancel account']);
    $this->drupalLogin($account);
    // Load a real user object.
    $user_storage->resetCache([$account->id()]);
    $account = $user_storage->load($account->id());

    // Create a simple node.
    $node = $this->drupalCreateNode(['uid' => $account->id()]);

    // Add a comment to the page.
    $comment_subject = $this->randomMachineName(8);
    $comment_body = $this->randomMachineName(8);
    $comment = Comment::create([
      'subject' => $comment_subject,
      'comment_body' => $comment_body,
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'status' => CommentInterface::PUBLISHED,
      'uid' => $account->id(),
    ]);
    $comment->save();

    // Create a node with two revisions, the initial one belonging to the
    // cancelling user.
    $revision_node = $this->drupalCreateNode(['uid' => $account->id()]);
    $revision = $revision_node->getRevisionId();
    $settings = get_object_vars($revision_node);
    $settings['revision'] = 1;
    // Set new/current revision to someone else.
    $settings['uid'] = 1;
    $revision_node = $this->drupalCreateNode($settings);

    // Attempt to cancel account.
    $this->drupalGet('user/' . $account->id() . '/cancel');
    $this->assertSession()->pageTextContains('Are you sure you want to cancel your account?');
    $this->assertSession()->pageTextContains("Your account will be removed and all account information deleted. All of your content will be assigned to the {$this->config('user.settings')->get('anonymous')} user.");

    // Confirm account cancellation.
    $timestamp = time();
    $this->submitForm([], 'Confirm');
    $this->assertSession()->pageTextContains('A confirmation request to cancel your account has been sent to your email address.');

    // Confirm account cancellation request.
    $this->drupalGet("user/" . $account->id() . "/cancel/confirm/$timestamp/" . user_pass_rehash($account, $timestamp));
    $user_storage->resetCache([$account->id()]);
    $this->assertNull($user_storage->load($account->id()), 'User is not found in the database.');

    // Confirm that user's content has been attributed to anonymous user.
    $anonymous_user = User::getAnonymousUser();
    $node_storage->resetCache([$node->id()]);
    $test_node = $node_storage->load($node->id());
    $this->assertEquals(0, $test_node->getOwnerId(), 'Node of the user has been attributed to anonymous user.');
    $this->assertTrue($test_node->isPublished());
    $test_node = $node_storage->loadRevision($revision);
    $this->assertEquals(0, $test_node->getRevisionUser()->id(), 'Node revision of the user has been attributed to anonymous user.');
    $this->assertTrue($test_node->isPublished());
    $node_storage->resetCache([$revision_node->id()]);
    $test_node = $node_storage->load($revision_node->id());
    $this->assertNotEquals(0, $test_node->getOwnerId(), "Current revision of the user's node was not attributed to anonymous user.");
    $this->assertTrue($test_node->isPublished());

    $storage = \Drupal::entityTypeManager()->getStorage('comment');
    $storage->resetCache([$comment->id()]);
    $test_comment = $storage->load($comment->id());
    $this->assertEquals(0, $test_comment->getOwnerId(), 'Comment of the user has been attributed to anonymous user.');
    $this->assertTrue($test_comment->isPublished());
    $this->assertEquals($anonymous_user->getDisplayName(), $test_comment->getAuthorName(), 'Comment of the user has been attributed to anonymous user name.');

    // Confirm that the confirmation message made it through to the end user.
    $this->assertSession()->pageTextContains("Account {$account->getAccountName()} has been deleted.");
  }

  /**
   * Delete account and anonymize all content using a batch process.
   */
  public function testUserAnonymizeBatch(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $this->config('user.settings')->set('cancel_method', 'user_cancel_reassign')->save();
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');

    // Create a user.
    $account = $this->drupalCreateUser(['cancel account']);
    $this->drupalLogin($account);
    // Load a real user object.
    $user_storage->resetCache([$account->id()]);
    $account = $user_storage->load($account->id());

    // Create 11 nodes in order to trigger batch processing in
    // node_mass_update().
    $nodes = [];
    for ($i = 0; $i < 11; $i++) {
      $node = $this->drupalCreateNode(['uid' => $account->id()]);
      $nodes[$node->id()] = $node;
    }

    // Attempt to cancel account.
    $this->drupalGet('user/' . $account->id() . '/cancel');
    $this->assertSession()->pageTextContains('Are you sure you want to cancel your account?');
    $this->assertSession()->pageTextContains("Your account will be removed and all account information deleted. All of your content will be assigned to the {$this->config('user.settings')->get('anonymous')} user.");

    // Confirm account cancellation.
    $timestamp = time();
    $this->submitForm([], 'Confirm');
    $this->assertSession()->pageTextContains('A confirmation request to cancel your account has been sent to your email address.');

    // Confirm account cancellation request.
    $this->drupalGet("user/" . $account->id() . "/cancel/confirm/$timestamp/" . user_pass_rehash($account, $timestamp));
    $user_storage->resetCache([$account->id()]);
    $this->assertNull($user_storage->load($account->id()), 'User is not found in the database.');

    // Confirm that user's content has been attributed to anonymous user.
    $node_storage->resetCache(array_keys($nodes));
    $test_nodes = $node_storage->loadMultiple(array_keys($nodes));
    foreach ($test_nodes as $test_node) {
      $this->assertEquals(0, $test_node->getOwnerId(), 'Node ' . $test_node->id() . ' of the user has been attributed to anonymous user.');
      $this->assertTrue($test_node->isPublished());
    }
  }

  /**
   * Delete account and remove all content.
   */
  public function testUserDelete(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $this->config('user.settings')->set('cancel_method', 'user_cancel_delete')->save();
    \Drupal::service('module_installer')->install(['comment']);
    $this->resetAll();
    $this->addDefaultCommentField('node', 'page');
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');

    // Create a user.
    $account = $this->drupalCreateUser([
      'cancel account',
      'post comments',
      'skip comment approval',
    ]);
    $this->drupalLogin($account);
    // Load a real user object.
    $user_storage->resetCache([$account->id()]);
    $account = $user_storage->load($account->id());

    // Create a simple node.
    $node = $this->drupalCreateNode(['uid' => $account->id()]);

    // Create comment.
    $edit = [];
    $edit['subject[0][value]'] = $this->randomMachineName(8);
    $edit['comment_body[0][value]'] = $this->randomMachineName(16);

    $this->drupalGet('comment/reply/node/' . $node->id() . '/comment');
    $this->submitForm($edit, 'Preview');
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Your comment has been posted.');
    $comments = \Drupal::entityTypeManager()->getStorage('comment')->loadByProperties(['subject' => $edit['subject[0][value]']]);
    $comment = reset($comments);
    $this->assertNotEmpty($comment->id(), 'Comment found.');

    // Create a node with two revisions, the initial one belonging to the
    // cancelling user.
    $revision_node = $this->drupalCreateNode(['uid' => $account->id()]);
    $revision = $revision_node->getRevisionId();
    $settings = get_object_vars($revision_node);
    $settings['revision'] = 1;
    // Set new/current revision to someone else.
    $settings['uid'] = 1;
    $revision_node = $this->drupalCreateNode($settings);

    // Attempt to cancel account.
    $this->drupalGet('user/' . $account->id() . '/cancel');
    $this->assertSession()->pageTextContains('Are you sure you want to cancel your account?');
    $this->assertSession()->pageTextContains('Your account will be removed and all account information deleted. All of your content will also be deleted.');

    // Confirm account cancellation.
    $timestamp = time();
    $this->submitForm([], 'Confirm');
    $this->assertSession()->pageTextContains('A confirmation request to cancel your account has been sent to your email address.');

    // Confirm account cancellation request.
    $this->drupalGet("user/" . $account->id() . "/cancel/confirm/$timestamp/" . user_pass_rehash($account, $timestamp));
    $user_storage->resetCache([$account->id()]);
    $this->assertNull($user_storage->load($account->id()), 'User is not found in the database.');

    // Confirm there's only one session in the database. The user will be logged
    // out and their session migrated.
    // @see _user_cancel_session_regenerate()
    $this->assertSame(1, (int) \Drupal::database()->select('sessions', 's')->countQuery()->execute()->fetchField());

    // Confirm that user's content has been deleted.
    $node_storage->resetCache([$node->id()]);
    $this->assertNull($node_storage->load($node->id()), 'Node of the user has been deleted.');
    $this->assertNull($node_storage->loadRevision($revision), 'Node revision of the user has been deleted.');
    $node_storage->resetCache([$revision_node->id()]);
    $this->assertInstanceOf(Node::class, $node_storage->load($revision_node->id()));
    \Drupal::entityTypeManager()->getStorage('comment')->resetCache([$comment->id()]);
    $this->assertNull(Comment::load($comment->id()), 'Comment of the user has been deleted.');

    // Confirm that the confirmation message made it through to the end user.
    $this->assertSession()->pageTextContains("Account {$account->getAccountName()} has been deleted.");
  }

  /**
   * Create an administrative user and delete another user.
   */
  public function testUserCancelByAdmin(): void {
    $this->config('user.settings')->set('cancel_method', 'user_cancel_reassign')->save();

    // Create a regular user.
    $account = $this->drupalCreateUser([]);

    // Create administrative user.
    $admin_user = $this->drupalCreateUser(['administer users']);
    $this->drupalLogin($admin_user);

    // Delete regular user.
    $this->drupalGet('user/' . $account->id() . '/cancel');
    $this->assertSession()->pageTextContains("Are you sure you want to cancel the account {$account->getAccountName()}?");
    $this->assertSession()->pageTextContains('Cancellation method');

    // Confirm deletion.
    $this->submitForm([], 'Confirm');
    $this->assertSession()->pageTextContains("Account {$account->getAccountName()} has been deleted.");
    $this->assertNull(User::load($account->id()), 'User is not found in the database.');
  }

  /**
   * Tests deletion of a user account without an email address.
   */
  public function testUserWithoutEmailCancelByAdmin(): void {
    $this->config('user.settings')->set('cancel_method', 'user_cancel_reassign')->save();

    // Create a regular user.
    $account = $this->drupalCreateUser([]);
    // This user has no email address.
    $account->mail = '';
    $account->save();

    // Create administrative user.
    $admin_user = $this->drupalCreateUser(['administer users']);
    $this->drupalLogin($admin_user);

    // Delete regular user without email address.
    $this->drupalGet('user/' . $account->id() . '/cancel');
    $this->assertSession()->pageTextContains("Are you sure you want to cancel the account {$account->getAccountName()}?");
    $this->assertSession()->pageTextContains('Cancellation method');

    // Confirm deletion.
    $this->submitForm([], 'Confirm');
    $this->assertSession()->pageTextContains("Account {$account->getAccountName()} has been deleted.");
    $this->assertNull(User::load($account->id()), 'User is not found in the database.');
  }

  /**
   * Create an administrative user and mass-delete other users.
   */
  public function testMassUserCancelByAdmin(): void {
    \Drupal::service('module_installer')->install(['views']);
    $this->config('user.settings')->set('cancel_method', 'user_cancel_reassign')->save();
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');
    // Enable account cancellation notification.
    $this->config('user.settings')->set('notify.status_canceled', TRUE)->save();

    // Create administrative user.
    $admin_user = $this->drupalCreateUser(['administer users']);
    $this->drupalLogin($admin_user);

    // Create some users.
    $users = [];
    for ($i = 0; $i < 3; $i++) {
      $account = $this->drupalCreateUser([]);
      $users[$account->id()] = $account;
    }

    // Cancel user accounts, including own one.
    $edit = [];
    $edit['action'] = 'user_cancel_user_action';
    for ($i = 0; $i <= 4; $i++) {
      $edit['user_bulk_form[' . $i . ']'] = TRUE;
    }
    $this->drupalGet('admin/people');
    $this->submitForm($edit, 'Apply to selected items');
    $this->assertSession()->pageTextContains('Are you sure you want to cancel these user accounts?');
    $this->assertSession()->pageTextContains('Cancellation method');
    $this->assertSession()->pageTextContains('Require email confirmation');
    $this->assertSession()->pageTextContains('Notify user when account is canceled');

    // Confirm deletion.
    $this->submitForm([], 'Confirm');
    $status = TRUE;
    foreach ($users as $account) {
      $status = $status && (str_contains($this->getTextContent(), "Account {$account->getAccountName()} has been deleted."));
      $user_storage->resetCache([$account->id()]);
      $status = $status && !$user_storage->load($account->id());
    }
    $this->assertTrue($status, 'Users deleted and not found in the database.');

    // Ensure that admin account was not cancelled.
    $this->assertSession()->pageTextContains('A confirmation request to cancel your account has been sent to your email address.');
    $admin_user = $user_storage->load($admin_user->id());
    $this->assertTrue($admin_user->isActive(), 'Administrative user is found in the database and enabled.');

    // Verify that uid 1's account was not cancelled.
    $user_storage->resetCache([1]);
    $user1 = $user_storage->load(1);
    $this->assertTrue($user1->isActive(), 'User #1 still exists and is not blocked.');
  }

  /**
   * Tests user cancel with node access.
   */
  public function testUserDeleteWithContentAndNodeAccess(): void {

    \Drupal::service('module_installer')->install(['node_access_test']);
    // Rebuild node access.
    node_access_rebuild();

    $account = $this->drupalCreateUser(['access content']);
    $node = $this->drupalCreateNode(['type' => 'page', 'uid' => $account->id()]);
    $account->delete();
    $load2 = \Drupal::entityTypeManager()->getStorage('node')->load($node->id());
    $this->assertEmpty($load2);
  }

  /**
   * Delete account and anonymize all content and it's translations.
   */
  public function testUserAnonymizeTranslations(): void {
    $this->config('user.settings')->set('cancel_method', 'user_cancel_reassign')->save();
    // Create comment field on page.
    $this->addDefaultCommentField('node', 'page');
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');

    \Drupal::service('module_installer')->install([
      'language',
      'locale',
    ]);
    \Drupal::service('router.builder')->rebuildIfNeeded();
    ConfigurableLanguage::createFromLangcode('ur')->save();
    // Rebuild the container to update the default language container variable.
    $this->rebuildContainer();

    $account = $this->drupalCreateUser(['cancel account']);
    $this->drupalLogin($account);
    $user_storage->resetCache([$account->id()]);
    $account = $user_storage->load($account->id());

    $node = $this->drupalCreateNode(['uid' => $account->id()]);

    // Add a comment to the page.
    $comment_subject = $this->randomMachineName(8);
    $comment_body = $this->randomMachineName(8);
    $comment = Comment::create([
      'subject' => $comment_subject,
      'comment_body' => $comment_body,
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'status' => CommentInterface::PUBLISHED,
      'uid' => $account->id(),
    ]);
    $comment->save();
    $comment->addTranslation('ur', [
      'subject' => 'ur ' . $comment->label(),
      'status' => CommentInterface::PUBLISHED,
    ])->save();

    // Attempt to cancel account.
    $this->drupalGet('user/' . $account->id() . '/cancel');
    $this->assertSession()->pageTextContains('Are you sure you want to cancel your account?');
    $this->assertSession()->pageTextContains('Your account will be removed and all account information deleted. All of your content will be assigned to the ' . $this->config('user.settings')->get('anonymous') . ' user.');

    // Confirm account cancellation.
    $timestamp = time();
    $this->submitForm([], 'Confirm');
    $this->assertSession()->pageTextContains('A confirmation request to cancel your account has been sent to your email address.');

    // Confirm account cancellation request.
    $this->drupalGet('user/' . $account->id() . "/cancel/confirm/$timestamp/" . user_pass_rehash($account, $timestamp));
    $user_storage->resetCache([$account->id()]);
    $this->assertNull($user_storage->load($account->id()), 'User is not found in the database.');

    // Confirm that user's content has been attributed to anonymous user.
    $anonymous_user = User::getAnonymousUser();

    $storage = \Drupal::entityTypeManager()->getStorage('comment');
    $storage->resetCache([$comment->id()]);
    $test_comment = $storage->load($comment->id());
    $this->assertEquals(0, $test_comment->getOwnerId());
    $this->assertTrue($test_comment->isPublished(), 'Comment of the user has been attributed to anonymous user.');
    $this->assertEquals($anonymous_user->getDisplayName(), $test_comment->getAuthorName());

    $comment_translation = $test_comment->getTranslation('ur');
    $this->assertEquals(0, $comment_translation->getOwnerId());
    $this->assertTrue($comment_translation->isPublished(), 'Comment translation of the user has been attributed to anonymous user.');
    $this->assertEquals($anonymous_user->getDisplayName(), $comment_translation->getAuthorName());

    // Confirm that the confirmation message made it through to the end user.
    $this->assertSession()->responseContains(t('%name has been deleted.', ['%name' => $account->getAccountName()]));
  }

}
