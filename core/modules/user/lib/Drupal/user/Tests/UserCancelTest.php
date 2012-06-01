<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserCancelTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test cancelling a user.
 */
class UserCancelTest extends WebTestBase {
  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'Cancel account',
      'description' => 'Ensure that account cancellation methods work as expected.',
      'group' => 'User',
    );
  }

  function setUp() {
    parent::setUp('comment');
  }

  /**
   * Attempt to cancel account without permission.
   */
  function testUserCancelWithoutPermission() {
    variable_set('user_cancel_method', 'user_cancel_reassign');

    // Create a user.
    $account = $this->drupalCreateUser(array());
    $this->drupalLogin($account);
    // Load real user object.
    $account = user_load($account->uid, TRUE);

    // Create a node.
    $node = $this->drupalCreateNode(array('uid' => $account->uid));

    // Attempt to cancel account.
    $this->drupalGet('user/' . $account->uid . '/edit');
    $this->assertNoRaw(t('Cancel account'), t('No cancel account button displayed.'));

    // Attempt bogus account cancellation request confirmation.
    $timestamp = $account->login;
    $this->drupalGet("user/$account->uid/cancel/confirm/$timestamp/" . user_pass_rehash($account->pass, $timestamp, $account->login));
    $this->assertResponse(403, t('Bogus cancelling request rejected.'));
    $account = user_load($account->uid);
    $this->assertTrue($account->status == 1, t('User account was not canceled.'));

    // Confirm user's content has not been altered.
    $test_node = node_load($node->nid, NULL, TRUE);
    $this->assertTrue(($test_node->uid == $account->uid && $test_node->status == 1), t('Node of the user has not been altered.'));
  }

  /**
   * Tests that user account for uid 1 cannot be cancelled.
   *
   * This should never be possible, or the site owner would become unable to
   * administer the site.
   */
  function testUserCancelUid1() {
    // Update uid 1's name and password to we know it.
    $password = user_password();
    require_once DRUPAL_ROOT . '/' . variable_get('password_inc', 'core/includes/password.inc');
    $account = array(
      'name' => 'user1',
      'pass' => user_hash_password(trim($password)),
    );
    // We cannot use $account->save() here, because this would result in the
    // password being hashed again.
    db_update('users')
      ->fields($account)
      ->condition('uid', 1)
      ->execute();

    // Reload and log in uid 1.
    $user1 = user_load(1, TRUE);
    $user1->pass_raw = $password;

    // Try to cancel uid 1's account with a different user.
    $this->admin_user = $this->drupalCreateUser(array('administer users'));
    $this->drupalLogin($this->admin_user);
    $edit = array(
      'operation' => 'cancel',
      'accounts[1]' => TRUE,
    );
    $this->drupalPost('admin/people', $edit, t('Update'));

    // Verify that uid 1's account was not cancelled.
    $user1 = user_load(1, TRUE);
    $this->assertEqual($user1->status, 1, t('User #1 still exists and is not blocked.'));
  }

  /**
   * Attempt invalid account cancellations.
   */
  function testUserCancelInvalid() {
    variable_set('user_cancel_method', 'user_cancel_reassign');

    // Create a user.
    $account = $this->drupalCreateUser(array('cancel account'));
    $this->drupalLogin($account);
    // Load real user object.
    $account = user_load($account->uid, TRUE);

    // Create a node.
    $node = $this->drupalCreateNode(array('uid' => $account->uid));

    // Attempt to cancel account.
    $this->drupalPost('user/' . $account->uid . '/edit', NULL, t('Cancel account'));

    // Confirm account cancellation.
    $timestamp = time();
    $this->drupalPost(NULL, NULL, t('Cancel account'));
    $this->assertText(t('A confirmation request to cancel your account has been sent to your e-mail address.'), t('Account cancellation request mailed message displayed.'));

    // Attempt bogus account cancellation request confirmation.
    $bogus_timestamp = $timestamp + 60;
    $this->drupalGet("user/$account->uid/cancel/confirm/$bogus_timestamp/" . user_pass_rehash($account->pass, $bogus_timestamp, $account->login));
    $this->assertText(t('You have tried to use an account cancellation link that has expired. Please request a new one using the form below.'), t('Bogus cancelling request rejected.'));
    $account = user_load($account->uid);
    $this->assertTrue($account->status == 1, t('User account was not canceled.'));

    // Attempt expired account cancellation request confirmation.
    $bogus_timestamp = $timestamp - 86400 - 60;
    $this->drupalGet("user/$account->uid/cancel/confirm/$bogus_timestamp/" . user_pass_rehash($account->pass, $bogus_timestamp, $account->login));
    $this->assertText(t('You have tried to use an account cancellation link that has expired. Please request a new one using the form below.'), t('Expired cancel account request rejected.'));
    $accounts = user_load_multiple(array($account->uid), array('status' => 1));
    $this->assertTrue(reset($accounts), t('User account was not canceled.'));

    // Confirm user's content has not been altered.
    $test_node = node_load($node->nid, NULL, TRUE);
    $this->assertTrue(($test_node->uid == $account->uid && $test_node->status == 1), t('Node of the user has not been altered.'));
  }

  /**
   * Disable account and keep all content.
   */
  function testUserBlock() {
    variable_set('user_cancel_method', 'user_cancel_block');

    // Create a user.
    $web_user = $this->drupalCreateUser(array('cancel account'));
    $this->drupalLogin($web_user);

    // Load real user object.
    $account = user_load($web_user->uid, TRUE);

    // Attempt to cancel account.
    $this->drupalGet('user/' . $account->uid . '/edit');
    $this->drupalPost(NULL, NULL, t('Cancel account'));
    $this->assertText(t('Are you sure you want to cancel your account?'), t('Confirmation form to cancel account displayed.'));
    $this->assertText(t('Your account will be blocked and you will no longer be able to log in. All of your content will remain attributed to your user name.'), t('Informs that all content will be remain as is.'));
    $this->assertNoText(t('Select the method to cancel the account above.'), t('Does not allow user to select account cancellation method.'));

    // Confirm account cancellation.
    $timestamp = time();

    $this->drupalPost(NULL, NULL, t('Cancel account'));
    $this->assertText(t('A confirmation request to cancel your account has been sent to your e-mail address.'), t('Account cancellation request mailed message displayed.'));

    // Confirm account cancellation request.
    $this->drupalGet("user/$account->uid/cancel/confirm/$timestamp/" . user_pass_rehash($account->pass, $timestamp, $account->login));
    $account = user_load($account->uid, TRUE);
    $this->assertTrue($account->status == 0, t('User has been blocked.'));

    // Confirm user is logged out.
    $this->assertNoText($account->name, t('Logged out.'));
  }

  /**
   * Disable account and unpublish all content.
   */
  function testUserBlockUnpublish() {
    variable_set('user_cancel_method', 'user_cancel_block_unpublish');

    // Create a user.
    $account = $this->drupalCreateUser(array('cancel account'));
    $this->drupalLogin($account);
    // Load real user object.
    $account = user_load($account->uid, TRUE);

    // Create a node with two revisions.
    $node = $this->drupalCreateNode(array('uid' => $account->uid));
    $settings = get_object_vars($node);
    $settings['revision'] = 1;
    $node = $this->drupalCreateNode($settings);

    // Attempt to cancel account.
    $this->drupalGet('user/' . $account->uid . '/edit');
    $this->drupalPost(NULL, NULL, t('Cancel account'));
    $this->assertText(t('Are you sure you want to cancel your account?'), t('Confirmation form to cancel account displayed.'));
    $this->assertText(t('Your account will be blocked and you will no longer be able to log in. All of your content will be hidden from everyone but administrators.'), t('Informs that all content will be unpublished.'));

    // Confirm account cancellation.
    $timestamp = time();
    $this->drupalPost(NULL, NULL, t('Cancel account'));
    $this->assertText(t('A confirmation request to cancel your account has been sent to your e-mail address.'), t('Account cancellation request mailed message displayed.'));

    // Confirm account cancellation request.
    $this->drupalGet("user/$account->uid/cancel/confirm/$timestamp/" . user_pass_rehash($account->pass, $timestamp, $account->login));
    $account = user_load($account->uid, TRUE);
    $this->assertTrue($account->status == 0, t('User has been blocked.'));

    // Confirm user's content has been unpublished.
    $test_node = node_load($node->nid, NULL, TRUE);
    $this->assertTrue($test_node->status == 0, t('Node of the user has been unpublished.'));
    $test_node = node_load($node->nid, $node->vid, TRUE);
    $this->assertTrue($test_node->status == 0, t('Node revision of the user has been unpublished.'));

    // Confirm user is logged out.
    $this->assertNoText($account->name, t('Logged out.'));
  }

  /**
   * Delete account and anonymize all content.
   */
  function testUserAnonymize() {
    variable_set('user_cancel_method', 'user_cancel_reassign');

    // Create a user.
    $account = $this->drupalCreateUser(array('cancel account'));
    $this->drupalLogin($account);
    // Load real user object.
    $account = user_load($account->uid, TRUE);

    // Create a simple node.
    $node = $this->drupalCreateNode(array('uid' => $account->uid));

    // Create a node with two revisions, the initial one belonging to the
    // cancelling user.
    $revision_node = $this->drupalCreateNode(array('uid' => $account->uid));
    $revision = $revision_node->vid;
    $settings = get_object_vars($revision_node);
    $settings['revision'] = 1;
    $settings['uid'] = 1; // Set new/current revision to someone else.
    $revision_node = $this->drupalCreateNode($settings);

    // Attempt to cancel account.
    $this->drupalGet('user/' . $account->uid . '/edit');
    $this->drupalPost(NULL, NULL, t('Cancel account'));
    $this->assertText(t('Are you sure you want to cancel your account?'), t('Confirmation form to cancel account displayed.'));
    $this->assertRaw(t('Your account will be removed and all account information deleted. All of your content will be assigned to the %anonymous-name user.', array('%anonymous-name' => variable_get('anonymous', t('Anonymous')))), t('Informs that all content will be attributed to anonymous account.'));

    // Confirm account cancellation.
    $timestamp = time();
    $this->drupalPost(NULL, NULL, t('Cancel account'));
    $this->assertText(t('A confirmation request to cancel your account has been sent to your e-mail address.'), t('Account cancellation request mailed message displayed.'));

    // Confirm account cancellation request.
    $this->drupalGet("user/$account->uid/cancel/confirm/$timestamp/" . user_pass_rehash($account->pass, $timestamp, $account->login));
    $this->assertFalse(user_load($account->uid, TRUE), t('User is not found in the database.'));

    // Confirm that user's content has been attributed to anonymous user.
    $test_node = node_load($node->nid, NULL, TRUE);
    $this->assertTrue(($test_node->uid == 0 && $test_node->status == 1), t('Node of the user has been attributed to anonymous user.'));
    $test_node = node_load($revision_node->nid, $revision, TRUE);
    $this->assertTrue(($test_node->revision_uid == 0 && $test_node->status == 1), t('Node revision of the user has been attributed to anonymous user.'));
    $test_node = node_load($revision_node->nid, NULL, TRUE);
    $this->assertTrue(($test_node->uid != 0 && $test_node->status == 1), t("Current revision of the user's node was not attributed to anonymous user."));

    // Confirm that user is logged out.
    $this->assertNoText($account->name, t('Logged out.'));
  }

  /**
   * Delete account and remove all content.
   */
  function testUserDelete() {
    variable_set('user_cancel_method', 'user_cancel_delete');

    // Create a user.
    $account = $this->drupalCreateUser(array('cancel account', 'post comments', 'skip comment approval'));
    $this->drupalLogin($account);
    // Load real user object.
    $account = user_load($account->uid, TRUE);

    // Create a simple node.
    $node = $this->drupalCreateNode(array('uid' => $account->uid));

    // Create comment.
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $edit = array();
    $edit['subject'] = $this->randomName(8);
    $edit['comment_body[' . $langcode . '][0][value]'] = $this->randomName(16);

    $this->drupalPost('comment/reply/' . $node->nid, $edit, t('Preview'));
    $this->drupalPost(NULL, array(), t('Save'));
    $this->assertText(t('Your comment has been posted.'));
    $comments = comment_load_multiple(FALSE, array('subject' => $edit['subject']));
    $comment = reset($comments);
    $this->assertTrue($comment->cid, t('Comment found.'));

    // Create a node with two revisions, the initial one belonging to the
    // cancelling user.
    $revision_node = $this->drupalCreateNode(array('uid' => $account->uid));
    $revision = $revision_node->vid;
    $settings = get_object_vars($revision_node);
    $settings['revision'] = 1;
    $settings['uid'] = 1; // Set new/current revision to someone else.
    $revision_node = $this->drupalCreateNode($settings);

    // Attempt to cancel account.
    $this->drupalGet('user/' . $account->uid . '/edit');
    $this->drupalPost(NULL, NULL, t('Cancel account'));
    $this->assertText(t('Are you sure you want to cancel your account?'), t('Confirmation form to cancel account displayed.'));
    $this->assertText(t('Your account will be removed and all account information deleted. All of your content will also be deleted.'), t('Informs that all content will be deleted.'));

    // Confirm account cancellation.
    $timestamp = time();
    $this->drupalPost(NULL, NULL, t('Cancel account'));
    $this->assertText(t('A confirmation request to cancel your account has been sent to your e-mail address.'), t('Account cancellation request mailed message displayed.'));

    // Confirm account cancellation request.
    $this->drupalGet("user/$account->uid/cancel/confirm/$timestamp/" . user_pass_rehash($account->pass, $timestamp, $account->login));
    $this->assertFalse(user_load($account->uid, TRUE), t('User is not found in the database.'));

    // Confirm that user's content has been deleted.
    $this->assertFalse(node_load($node->nid, NULL, TRUE), t('Node of the user has been deleted.'));
    $this->assertFalse(node_load($node->nid, $revision, TRUE), t('Node revision of the user has been deleted.'));
    $this->assertTrue(node_load($revision_node->nid, NULL, TRUE), t("Current revision of the user's node was not deleted."));
    $this->assertFalse(comment_load($comment->cid), t('Comment of the user has been deleted.'));

    // Confirm that user is logged out.
    $this->assertNoText($account->name, t('Logged out.'));
  }

  /**
   * Create an administrative user and delete another user.
   */
  function testUserCancelByAdmin() {
    variable_set('user_cancel_method', 'user_cancel_reassign');

    // Create a regular user.
    $account = $this->drupalCreateUser(array());

    // Create administrative user.
    $admin_user = $this->drupalCreateUser(array('administer users'));
    $this->drupalLogin($admin_user);

    // Delete regular user.
    $this->drupalGet('user/' . $account->uid . '/edit');
    $this->drupalPost(NULL, NULL, t('Cancel account'));
    $this->assertRaw(t('Are you sure you want to cancel the account %name?', array('%name' => $account->name)), t('Confirmation form to cancel account displayed.'));
    $this->assertText(t('Select the method to cancel the account above.'), t('Allows to select account cancellation method.'));

    // Confirm deletion.
    $this->drupalPost(NULL, NULL, t('Cancel account'));
    $this->assertRaw(t('%name has been deleted.', array('%name' => $account->name)), t('User deleted.'));
    $this->assertFalse(user_load($account->uid), t('User is not found in the database.'));
  }

  /**
   * Tests deletion of a user account without an e-mail address.
   */
  function testUserWithoutEmailCancelByAdmin() {
    variable_set('user_cancel_method', 'user_cancel_reassign');

    // Create a regular user.
    $account = $this->drupalCreateUser(array());
    // This user has no e-mail address.
    $account->mail = '';
    $account->save();

    // Create administrative user.
    $admin_user = $this->drupalCreateUser(array('administer users'));
    $this->drupalLogin($admin_user);

    // Delete regular user without e-mail address.
    $this->drupalGet('user/' . $account->uid . '/edit');
    $this->drupalPost(NULL, NULL, t('Cancel account'));
    $this->assertRaw(t('Are you sure you want to cancel the account %name?', array('%name' => $account->name)), t('Confirmation form to cancel account displayed.'));
    $this->assertText(t('Select the method to cancel the account above.'), t('Allows to select account cancellation method.'));

    // Confirm deletion.
    $this->drupalPost(NULL, NULL, t('Cancel account'));
    $this->assertRaw(t('%name has been deleted.', array('%name' => $account->name)), t('User deleted.'));
    $this->assertFalse(user_load($account->uid), t('User is not found in the database.'));
  }

  /**
   * Create an administrative user and mass-delete other users.
   */
  function testMassUserCancelByAdmin() {
    variable_set('user_cancel_method', 'user_cancel_reassign');
    // Enable account cancellation notification.
    variable_set('user_mail_status_canceled_notify', TRUE);

    // Create administrative user.
    $admin_user = $this->drupalCreateUser(array('administer users'));
    $this->drupalLogin($admin_user);

    // Create some users.
    $users = array();
    for ($i = 0; $i < 3; $i++) {
      $account = $this->drupalCreateUser(array());
      $users[$account->uid] = $account;
    }

    // Cancel user accounts, including own one.
    $edit = array();
    $edit['operation'] = 'cancel';
    foreach ($users as $uid => $account) {
      $edit['accounts[' . $uid . ']'] = TRUE;
    }
    $edit['accounts[' . $admin_user->uid . ']'] = TRUE;
    // Also try to cancel uid 1.
    $edit['accounts[1]'] = TRUE;
    $this->drupalPost('admin/people', $edit, t('Update'));
    $this->assertText(t('Are you sure you want to cancel these user accounts?'), t('Confirmation form to cancel accounts displayed.'));
    $this->assertText(t('When cancelling these accounts'), t('Allows to select account cancellation method.'));
    $this->assertText(t('Require e-mail confirmation to cancel account.'), t('Allows to send confirmation mail.'));
    $this->assertText(t('Notify user when account is canceled.'), t('Allows to send notification mail.'));

    // Confirm deletion.
    $this->drupalPost(NULL, NULL, t('Cancel accounts'));
    $status = TRUE;
    foreach ($users as $account) {
      $status = $status && (strpos($this->content, t('%name has been deleted.', array('%name' => $account->name))) !== FALSE);
      $status = $status && !user_load($account->uid, TRUE);
    }
    $this->assertTrue($status, t('Users deleted and not found in the database.'));

    // Ensure that admin account was not cancelled.
    $this->assertText(t('A confirmation request to cancel your account has been sent to your e-mail address.'), t('Account cancellation request mailed message displayed.'));
    $admin_user = user_load($admin_user->uid);
    $this->assertTrue($admin_user->status == 1, t('Administrative user is found in the database and enabled.'));

    // Verify that uid 1's account was not cancelled.
    $user1 = user_load(1, TRUE);
    $this->assertEqual($user1->status, 1, t('User #1 still exists and is not blocked.'));
  }
}
