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
    // Generate users.
    $user1 = $this->drupalCreateUser(array());
    $user2 = $this->drupalCreateUser(array());
    $user3 = $this->drupalCreateUser(array());

    // Update access of two users to be within the active timespan.
    $this->updateAccess($user1->uid);
    $this->updateAccess($user2->uid, REQUEST_TIME + 1);

    // Insert an inactive user who should not be seen in the block.
    $this->updateAccess($user3->uid, REQUEST_TIME - config('user.block')->get('seconds_online') - 1);

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
   * Updates the access column for a user.
   */
  private function updateAccess($uid, $access = REQUEST_TIME) {
    db_update('users')
      ->condition('uid', $uid)
      ->fields(array('access' => $access))
      ->execute();
  }
}
