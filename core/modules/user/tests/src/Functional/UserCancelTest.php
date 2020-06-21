<?php

namespace Drupal\Tests\user\Functional;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Ensure that account cancellation methods work as expected.
 *
 * @group user
 */
class UserCancelTest extends BrowserTestBase {

  use CommentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'comment'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
  }

  /**
   * Attempt to cancel account without permission.
   */
  public function testUserCancelWithoutPermission() {
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
    $this->assertNoRaw(t('Cancel account'), 'No cancel account button displayed.');

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
    $this->assertTrue(($test_node->getOwnerId() == $account->id() && $test_node->isPublished()), 'Node of the user has not been altered.');
  }

  /**
   * Test ability to change the permission for canceling users.
   */
  public function testUserCancelChangePermission() {
    \Drupal::service('module_installer')->install(['user_form_test']);
    \Drupal::service('router.builder')->rebuild();
    $this->config('user.settings')->set('cancel_method', 'user_cancel_reassign')->save();

    // Create a regular user.
    $account = $this->drupalCreateUser([]);

    $admin_user = $this->drupalCreateUser(['cancel other accounts']);
    $this->drupalLogin($admin_user);

    // Delete regular user.
    $this->drupalPostForm('user_form_test_cancel/' . $account->id(), [], t('Cancel account'));

    // Confirm deletion.
    $this->assertRaw(t('%name has been deleted.', ['%name' => $account->getAccountName()]), 'User deleted.');
    $this->assertNull(User::load($account->id()), 'User is not found in the database.');
  }

  /**
   * Tests that user account for uid 1 cannot be cancelled.
   *
   * This should never be possible, or the site owner would become unable to
   * administer the site.
   */
  public function testUserCancelUid1() {
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');

    \Drupal::service('module_installer')->install(['views']);
    \Drupal::service('router.builder')->rebuild();

    // Try to cancel uid 1's account with a different user.
    $admin_user = $this->drupalCreateUser(['administer users']);
    $this->drupalLogin($admin_user);
    $edit = [
      'action' => 'user_cancel_user_action',
      'user_bulk_form[0]' => TRUE,
    ];
    $this->drupalPostForm('admin/people', $edit, t('Apply to selected items'));

    // Verify that uid 1's account was not cancelled.
    $user_storage->resetCache([1]);
    $user1 = $user_storage->load(1);
    $this->assertTrue($user1->isActive(), 'User #1 still exists and is not blocked.');
  }

  /**
   * Attempt invalid account cancellations.
   */
  public function testUserCancelInvalid() {
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
    $this->drupalPostForm('user/' . $account->id() . '/edit', NULL, t('Cancel account'));

    // Confirm account cancellation.
    $timestamp = time();
    $this->drupalPostForm(NULL, NULL, t('Cancel account'));
    $this->assertText(t('A confirmation request to cancel your account has been sent to your email address.'), 'Account cancellation request mailed message displayed.');

    // Attempt bogus account cancellation request confirmation.
    $bogus_timestamp = $timestamp + 60;
    $this->drupalGet("user/" . $account->id() . "/cancel/confirm/$bogus_timestamp/" . user_pass_rehash($account, $bogus_timestamp));
    $this->assertText(t('You have tried to use an account cancellation link that has expired. Please request a new one using the form below.'), 'Bogus cancelling request rejected.');
    $user_storage->resetCache([$account->id()]);
    $account = $user_storage->load($account->id());
    $this->assertTrue($account->isActive(), 'User account was not canceled.');

    // Attempt expired account cancellation request confirmation.
    $bogus_timestamp = $timestamp - 86400 - 60;
    $this->drupalGet("user/" . $account->id() . "/cancel/confirm/$bogus_timestamp/" . user_pass_rehash($account, $bogus_timestamp));
    $this->assertText(t('You have tried to use an account cancellation link that has expired. Please request a new one using the form below.'), 'Expired cancel account request rejected.');
    $user_storage->resetCache([$account->id()]);
    $account = $user_storage->load($account->id());
    $this->assertTrue($account->isActive(), 'User account was not canceled.');

    // Confirm user's content has not been altered.
    $node_storage->resetCache([$node->id()]);
    $test_node = $node_storage->load($node->id());
    $this->assertTrue(($test_node->getOwnerId() == $account->id() && $test_node->isPublished()), 'Node of the user has not been altered.');
  }

  /**
   * Disable account and keep all content.
   */
  public function testUserBlock() {
    $this->config('user.settings')->set('cancel_method', 'user_cancel_block')->save();
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');

    // Create a user.
    $web_user = $this->drupalCreateUser(['cancel account']);
    $this->drupalLogin($web_user);

    // Load a real user object.
    $user_storage->resetCache([$web_user->id()]);
    $account = $user_storage->load($web_user->id());

    // Attempt to cancel account.
    $this->drupalGet('user/' . $account->id() . '/edit');
    $this->drupalPostForm(NULL, NULL, t('Cancel account'));
    $this->assertText(t('Are you sure you want to cancel your account?'), 'Confirmation form to cancel account displayed.');
    $this->assertText(t('Your account will be blocked and you will no longer be able to log in. All of your content will remain attributed to your username.'), 'Informs that all content will be remain as is.');
    $this->assertNoText(t('Select the method to cancel the account above.'), 'Does not allow user to select account cancellation method.');

    // Confirm account cancellation.
    $timestamp = time();

    $this->drupalPostForm(NULL, NULL, t('Cancel account'));
    $this->assertText(t('A confirmation request to cancel your account has been sent to your email address.'), 'Account cancellation request mailed message displayed.');

    // Confirm account cancellation request.
    $this->drupalGet("user/" . $account->id() . "/cancel/confirm/$timestamp/" . user_pass_rehash($account, $timestamp));
    $user_storage->resetCache([$account->id()]);
    $account = $user_storage->load($account->id());
    $this->assertTrue($account->isBlocked(), 'User has been blocked.');

    // Confirm that the confirmation message made it through to the end user.
    $this->assertRaw(t('%name has been disabled.', ['%name' => $account->getAccountName()]), "Confirmation message displayed to user.");
  }

  /**
   * Disable account and unpublish all content.
   */
  public function testUserBlockUnpublish() {
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
    $this->drupalGet('user/' . $account->id() . '/edit');
    $this->drupalPostForm(NULL, NULL, t('Cancel account'));
    $this->assertText(t('Are you sure you want to cancel your account?'), 'Confirmation form to cancel account displayed.');
    $this->assertText(t('Your account will be blocked and you will no longer be able to log in. All of your content will be hidden from everyone but administrators.'), 'Informs that all content will be unpublished.');

    // Confirm account cancellation.
    $timestamp = time();
    $this->drupalPostForm(NULL, NULL, t('Cancel account'));
    $this->assertText(t('A confirmation request to cancel your account has been sent to your email address.'), 'Account cancellation request mailed message displayed.');

    // Confirm account cancellation request.
    $this->drupalGet("user/" . $account->id() . "/cancel/confirm/$timestamp/" . user_pass_rehash($account, $timestamp));
    // Confirm that the user was redirected to the front page.
    $this->assertSession()->addressEquals('');
    $this->assertSession()->statusCodeEquals(200);
    // Confirm that the confirmation message made it through to the end user.
    $this->assertRaw(t('%name has been disabled.', ['%name' => $account->getAccountName()]), "Confirmation message displayed to user.");

    $user_storage->resetCache([$account->id()]);
    $account = $user_storage->load($account->id());
    $this->assertTrue($account->isBlocked(), 'User has been blocked.');

    // Confirm user's content has been unpublished.
    $node_storage->resetCache([$node->id()]);
    $test_node = $node_storage->load($node->id());
    $this->assertFalse($test_node->isPublished(), 'Node of the user has been unpublished.');
    $test_node = node_revision_load($node->getRevisionId());
    $this->assertFalse($test_node->isPublished(), 'Node revision of the user has been unpublished.');

    $storage = \Drupal::entityTypeManager()->getStorage('comment');
    $storage->resetCache([$comment->id()]);
    $comment = $storage->load($comment->id());
    $this->assertFalse($comment->isPublished(), 'Comment of the user has been unpublished.');
  }

  /**
   * Delete account and anonymize all content.
   */
  public function testUserAnonymize() {
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
    $this->drupalGet('user/' . $account->id() . '/edit');
    $this->drupalPostForm(NULL, NULL, t('Cancel account'));
    $this->assertText(t('Are you sure you want to cancel your account?'), 'Confirmation form to cancel account displayed.');
    $this->assertRaw(t('Your account will be removed and all account information deleted. All of your content will be assigned to the %anonymous-name user.', ['%anonymous-name' => $this->config('user.settings')->get('anonymous')]), 'Informs that all content will be attributed to anonymous account.');

    // Confirm account cancellation.
    $timestamp = time();
    $this->drupalPostForm(NULL, NULL, t('Cancel account'));
    $this->assertText(t('A confirmation request to cancel your account has been sent to your email address.'), 'Account cancellation request mailed message displayed.');

    // Confirm account cancellation request.
    $this->drupalGet("user/" . $account->id() . "/cancel/confirm/$timestamp/" . user_pass_rehash($account, $timestamp));
    $user_storage->resetCache([$account->id()]);
    $this->assertNull($user_storage->load($account->id()), 'User is not found in the database.');

    // Confirm that user's content has been attributed to anonymous user.
    $anonymous_user = User::getAnonymousUser();
    $node_storage->resetCache([$node->id()]);
    $test_node = $node_storage->load($node->id());
    $this->assertTrue(($test_node->getOwnerId() == 0 && $test_node->isPublished()), 'Node of the user has been attributed to anonymous user.');
    $test_node = node_revision_load($revision, TRUE);
    $this->assertTrue(($test_node->getRevisionUser()->id() == 0 && $test_node->isPublished()), 'Node revision of the user has been attributed to anonymous user.');
    $node_storage->resetCache([$revision_node->id()]);
    $test_node = $node_storage->load($revision_node->id());
    $this->assertTrue(($test_node->getOwnerId() != 0 && $test_node->isPublished()), "Current revision of the user's node was not attributed to anonymous user.");

    $storage = \Drupal::entityTypeManager()->getStorage('comment');
    $storage->resetCache([$comment->id()]);
    $test_comment = $storage->load($comment->id());
    $this->assertTrue(($test_comment->getOwnerId() == 0 && $test_comment->isPublished()), 'Comment of the user has been attributed to anonymous user.');
    $this->assertEqual($test_comment->getAuthorName(), $anonymous_user->getDisplayName(), 'Comment of the user has been attributed to anonymous user name.');

    // Confirm that the confirmation message made it through to the end user.
    $this->assertRaw(t('%name has been deleted.', ['%name' => $account->getAccountName()]), "Confirmation message displayed to user.");
  }

  /**
   * Delete account and anonymize all content using a batch process.
   */
  public function testUserAnonymizeBatch() {
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
    $this->drupalGet('user/' . $account->id() . '/edit');
    $this->drupalPostForm(NULL, NULL, t('Cancel account'));
    $this->assertText(t('Are you sure you want to cancel your account?'), 'Confirmation form to cancel account displayed.');
    $this->assertRaw(t('Your account will be removed and all account information deleted. All of your content will be assigned to the %anonymous-name user.', ['%anonymous-name' => $this->config('user.settings')->get('anonymous')]), 'Informs that all content will be attributed to anonymous account.');

    // Confirm account cancellation.
    $timestamp = time();
    $this->drupalPostForm(NULL, NULL, t('Cancel account'));
    $this->assertText(t('A confirmation request to cancel your account has been sent to your email address.'), 'Account cancellation request mailed message displayed.');

    // Confirm account cancellation request.
    $this->drupalGet("user/" . $account->id() . "/cancel/confirm/$timestamp/" . user_pass_rehash($account, $timestamp));
    $user_storage->resetCache([$account->id()]);
    $this->assertNull($user_storage->load($account->id()), 'User is not found in the database.');

    // Confirm that user's content has been attributed to anonymous user.
    $node_storage->resetCache(array_keys($nodes));
    $test_nodes = $node_storage->loadMultiple(array_keys($nodes));
    foreach ($test_nodes as $test_node) {
      $this->assertTrue(($test_node->getOwnerId() == 0 && $test_node->isPublished()), 'Node ' . $test_node->id() . ' of the user has been attributed to anonymous user.');
    }
  }

  /**
   * Delete account and remove all content.
   */
  public function testUserDelete() {
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

    $this->drupalPostForm('comment/reply/node/' . $node->id() . '/comment', $edit, t('Preview'));
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText(t('Your comment has been posted.'));
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
    $this->drupalGet('user/' . $account->id() . '/edit');
    $this->drupalPostForm(NULL, NULL, t('Cancel account'));
    $this->assertText(t('Are you sure you want to cancel your account?'), 'Confirmation form to cancel account displayed.');
    $this->assertText(t('Your account will be removed and all account information deleted. All of your content will also be deleted.'), 'Informs that all content will be deleted.');

    // Confirm account cancellation.
    $timestamp = time();
    $this->drupalPostForm(NULL, NULL, t('Cancel account'));
    $this->assertText(t('A confirmation request to cancel your account has been sent to your email address.'), 'Account cancellation request mailed message displayed.');

    // Confirm account cancellation request.
    $this->drupalGet("user/" . $account->id() . "/cancel/confirm/$timestamp/" . user_pass_rehash($account, $timestamp));
    $user_storage->resetCache([$account->id()]);
    $this->assertNull($user_storage->load($account->id()), 'User is not found in the database.');

    // Confirm that user's content has been deleted.
    $node_storage->resetCache([$node->id()]);
    $this->assertNull($node_storage->load($node->id()), 'Node of the user has been deleted.');
    $this->assertNull(node_revision_load($revision), 'Node revision of the user has been deleted.');
    $node_storage->resetCache([$revision_node->id()]);
    $this->assertInstanceOf(Node::class, $node_storage->load($revision_node->id()));
    \Drupal::entityTypeManager()->getStorage('comment')->resetCache([$comment->id()]);
    $this->assertNull(Comment::load($comment->id()), 'Comment of the user has been deleted.');

    // Confirm that the confirmation message made it through to the end user.
    $this->assertRaw(t('%name has been deleted.', ['%name' => $account->getAccountName()]), "Confirmation message displayed to user.");
  }

  /**
   * Create an administrative user and delete another user.
   */
  public function testUserCancelByAdmin() {
    $this->config('user.settings')->set('cancel_method', 'user_cancel_reassign')->save();

    // Create a regular user.
    $account = $this->drupalCreateUser([]);

    // Create administrative user.
    $admin_user = $this->drupalCreateUser(['administer users']);
    $this->drupalLogin($admin_user);

    // Delete regular user.
    $this->drupalGet('user/' . $account->id() . '/edit');
    $this->drupalPostForm(NULL, NULL, t('Cancel account'));
    $this->assertRaw(t('Are you sure you want to cancel the account %name?', ['%name' => $account->getAccountName()]), 'Confirmation form to cancel account displayed.');
    $this->assertText(t('Select the method to cancel the account above.'), 'Allows to select account cancellation method.');

    // Confirm deletion.
    $this->drupalPostForm(NULL, NULL, t('Cancel account'));
    $this->assertRaw(t('%name has been deleted.', ['%name' => $account->getAccountName()]), 'User deleted.');
    $this->assertNull(User::load($account->id()), 'User is not found in the database.');
  }

  /**
   * Tests deletion of a user account without an email address.
   */
  public function testUserWithoutEmailCancelByAdmin() {
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
    $this->drupalGet('user/' . $account->id() . '/edit');
    $this->drupalPostForm(NULL, NULL, t('Cancel account'));
    $this->assertRaw(t('Are you sure you want to cancel the account %name?', ['%name' => $account->getAccountName()]), 'Confirmation form to cancel account displayed.');
    $this->assertText(t('Select the method to cancel the account above.'), 'Allows to select account cancellation method.');

    // Confirm deletion.
    $this->drupalPostForm(NULL, NULL, t('Cancel account'));
    $this->assertRaw(t('%name has been deleted.', ['%name' => $account->getAccountName()]), 'User deleted.');
    $this->assertNull(User::load($account->id()), 'User is not found in the database.');
  }

  /**
   * Create an administrative user and mass-delete other users.
   */
  public function testMassUserCancelByAdmin() {
    \Drupal::service('module_installer')->install(['views']);
    \Drupal::service('router.builder')->rebuild();
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
    $this->drupalPostForm('admin/people', $edit, t('Apply to selected items'));
    $this->assertText(t('Are you sure you want to cancel these user accounts?'), 'Confirmation form to cancel accounts displayed.');
    $this->assertText(t('When cancelling these accounts'), 'Allows to select account cancellation method.');
    $this->assertText(t('Require email confirmation to cancel account'), 'Allows to send confirmation mail.');
    $this->assertText(t('Notify user when account is canceled'), 'Allows to send notification mail.');

    // Confirm deletion.
    $this->drupalPostForm(NULL, NULL, t('Cancel accounts'));
    $status = TRUE;
    foreach ($users as $account) {
      $status = $status && (strpos($this->getTextContent(), $account->getAccountName() . ' has been deleted.') !== FALSE);
      $user_storage->resetCache([$account->id()]);
      $status = $status && !$user_storage->load($account->id());
    }
    $this->assertTrue($status, 'Users deleted and not found in the database.');

    // Ensure that admin account was not cancelled.
    $this->assertText(t('A confirmation request to cancel your account has been sent to your email address.'), 'Account cancellation request mailed message displayed.');
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
  public function testUserDeleteWithContentAndNodeAccess() {

    \Drupal::service('module_installer')->install(['node_access_test']);
    // Rebuild node access.
    node_access_rebuild();

    $account = $this->drupalCreateUser(['access content']);
    $node = $this->drupalCreateNode(['type' => 'page', 'uid' => $account->id()]);
    $account->delete();
    $load2 = \Drupal::entityTypeManager()->getStorage('node')->load($node->id());
    $this->assertTrue(empty($load2));
  }

}
