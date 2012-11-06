<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserBlocksTests.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test user blocks.
 */
class UserBlocksTests extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block');

  public static function getInfo() {
    return array(
      'name' => 'User blocks',
      'description' => 'Test user blocks.',
      'group' => 'User'
    );
  }

  function setUp() {
    parent::setUp();

    // Enable user login block.
    db_merge('block')
      ->key(array(
        'module' => 'user',
        'delta' => 'login',
        'theme' => variable_get('theme_default', 'stark'),
      ))
      ->fields(array(
        'status' => 1,
        'weight' => 0,
        'region' => 'sidebar_first',
        'pages' => '',
        'cache' => -1,
      ))
      ->execute();
  }

  /**
   * Test the user login block.
   */
  function testUserLoginBlock() {
    // Create a user with some permission that anonymous users lack.
    $user = $this->drupalCreateUser(array('administer permissions'));

    // Log in using the block.
    $edit = array();
    $edit['name'] = $user->name;
    $edit['pass'] = $user->pass_raw;
    $this->drupalPost('admin/people/permissions', $edit, t('Log in'));
    $this->assertNoText(t('User login'), 'Logged in.');

    // Check that we are still on the same page.
    $this->assertEqual(url('admin/people/permissions', array('absolute' => TRUE)), $this->getUrl(), 'Still on the same page after login for access denied page');

    // Now, log out and repeat with a non-403 page.
    $this->drupalLogout();
    $this->drupalPost('filter/tips', $edit, t('Log in'));
    $this->assertNoText(t('User login'), 'Logged in.');
    $this->assertPattern('!<title.*?' . t('Compose tips') . '.*?</title>!', 'Still on the same page after login for allowed page');

    // Check that the user login block is not vulnerable to information
    // disclosure to third party sites.
    $this->drupalLogout();
    $this->drupalPost('http://example.com/', $edit, t('Log in'), array('external' => FALSE));
    // Check that we remain on the site after login.
    $this->assertEqual(url('user/' . $user->uid, array('absolute' => TRUE)), $this->getUrl(), 'Redirected to user profile page after login from the frontpage');
  }

  /**
   * Test the Who's Online block.
   */
  function testWhosOnlineBlock() {
    // Generate users and make sure there are no current user sessions.
    $user1 = $this->drupalCreateUser(array());
    $user2 = $this->drupalCreateUser(array());
    $user3 = $this->drupalCreateUser(array());
    $this->assertEqual(db_query("SELECT COUNT(*) FROM {sessions}")->fetchField(), 0, 'Sessions table is empty.');

    // Insert a user with two sessions.
    $this->insertSession(array('uid' => $user1->uid));
    $this->insertSession(array('uid' => $user1->uid));
    $this->assertEqual(db_query("SELECT COUNT(*) FROM {sessions} WHERE uid = :uid", array(':uid' => $user1->uid))->fetchField(), 2, 'Duplicate user session has been inserted.');

    // Insert a user with only one session.
    $this->insertSession(array('uid' => $user2->uid, 'timestamp' => REQUEST_TIME + 1));

    // Insert an inactive logged-in user who should not be seen in the block.
    $this->insertSession(array('uid' => $user3->uid, 'timestamp' => (REQUEST_TIME - config('user.block')->get('seconds_online') - 1)));

    // Insert two anonymous user sessions.
    $this->insertSession();
    $this->insertSession();

    // Test block output.
    $block = user_block_view('online');
    $block['content'] = render($block['content']);
    $this->drupalSetContent($block['content']);
    $this->assertRaw(t('2 users'), 'Correct number of online users (2 users).');
    $this->assertText($user1->name, 'Active user 1 found in online list.');
    $this->assertText($user2->name, 'Active user 2 found in online list.');
    $this->assertNoText($user3->name, 'Inactive user not found in online list.');
    $this->assertTrue(strpos($this->drupalGetContent(), $user1->name) > strpos($this->drupalGetContent(), $user2->name), 'Online users are ordered correctly.');
  }

  /**
   * Insert a user session into the {sessions} table. This function is used
   * since we cannot log in more than one user at the same time in tests.
   */
  private function insertSession(array $fields = array()) {
    $fields += array(
      'uid' => 0,
      'sid' => drupal_hash_base64(uniqid(mt_rand(), TRUE)),
      'timestamp' => REQUEST_TIME,
    );
    db_insert('sessions')
      ->fields($fields)
      ->execute();
    $this->assertEqual(db_query("SELECT COUNT(*) FROM {sessions} WHERE uid = :uid AND sid = :sid AND timestamp = :timestamp", array(':uid' => $fields['uid'], ':sid' => $fields['sid'], ':timestamp' => $fields['timestamp']))->fetchField(), 1, 'Session record inserted.');
  }
}
