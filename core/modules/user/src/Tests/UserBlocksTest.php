<?php

namespace Drupal\user\Tests;

use Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber;
use Drupal\simpletest\WebTestBase;

/**
 * Tests user blocks.
 *
 * @group user
 */
class UserBlocksTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'views'];

  /**
   * A user with the 'administer blocks' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer blocks']);
    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('user_login_block');
    $this->drupalLogout($this->adminUser);
  }

  /**
   * Tests that user login block is hidden from user/login.
   */
  public function testUserLoginBlockVisibility() {
    // Array keyed list where key being the URL address and value being expected
    // visibility as boolean type.
    $paths = [
      'node' => TRUE,
      'user/login' => FALSE,
      'user/register' => TRUE,
      'user/password' => TRUE,
    ];
    foreach ($paths as $path => $expected_visibility) {
      $this->drupalGet($path);
      $elements = $this->xpath('//div[contains(@class,"block-user-login-block") and @role="form"]');
      if ($expected_visibility) {
        $this->assertTrue(!empty($elements), 'User login block in path "' . $path . '" should be visible');
      }
      else {
        $this->assertTrue(empty($elements), 'User login block in path "' . $path . '" should not be visible');
      }
    }
  }

  /**
   * Test the user login block.
   */
  public function testUserLoginBlock() {
    // Create a user with some permission that anonymous users lack.
    $user = $this->drupalCreateUser(['administer permissions']);

    // Log in using the block.
    $edit = [];
    $edit['name'] = $user->getUsername();
    $edit['pass'] = $user->pass_raw;
    $this->drupalPostForm('admin/people/permissions', $edit, t('Log in'));
    $this->assertNoText(t('User login'), 'Logged in.');

    // Check that we are still on the same page.
    $this->assertUrl(\Drupal::url('user.admin_permissions', [], ['absolute' => TRUE]), [], 'Still on the same page after login for access denied page');

    // Now, log out and repeat with a non-403 page.
    $this->drupalLogout();
    $this->drupalGet('filter/tips');
    $this->assertEqual('MISS', $this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER));
    $this->drupalPostForm(NULL, $edit, t('Log in'));
    $this->assertNoText(t('User login'), 'Logged in.');
    $this->assertPattern('!<title.*?' . t('Compose tips') . '.*?</title>!', 'Still on the same page after login for allowed page');

    // Log out again and repeat with a non-403 page including query arguments.
    $this->drupalLogout();
    $this->drupalGet('filter/tips', ['query' => ['foo' => 'bar']]);
    $this->assertEqual('HIT', $this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER));
    $this->drupalPostForm(NULL, $edit, t('Log in'));
    $this->assertNoText(t('User login'), 'Logged in.');
    $this->assertPattern('!<title.*?' . t('Compose tips') . '.*?</title>!', 'Still on the same page after login for allowed page');
    $this->assertTrue(strpos($this->getUrl(), '/filter/tips?foo=bar') !== FALSE, 'Correct query arguments are displayed after login');

    // Repeat with different query arguments.
    $this->drupalLogout();
    $this->drupalGet('filter/tips', ['query' => ['foo' => 'baz']]);
    $this->assertEqual('HIT', $this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER));
    $this->drupalPostForm(NULL, $edit, t('Log in'));
    $this->assertNoText(t('User login'), 'Logged in.');
    $this->assertPattern('!<title.*?' . t('Compose tips') . '.*?</title>!', 'Still on the same page after login for allowed page');
    $this->assertTrue(strpos($this->getUrl(), '/filter/tips?foo=baz') !== FALSE, 'Correct query arguments are displayed after login');

    // Check that the user login block is not vulnerable to information
    // disclosure to third party sites.
    $this->drupalLogout();
    $this->drupalPostForm('http://example.com/', $edit, t('Log in'), ['external' => FALSE]);
    // Check that we remain on the site after login.
    $this->assertUrl($user->url('canonical', ['absolute' => TRUE]), [], 'Redirected to user profile page after login from the frontpage');

    // Verify that form validation errors are displayed immediately for forms
    // in blocks and not on subsequent page requests.
    $this->drupalLogout();
    $edit = [];
    $edit['name'] = 'foo';
    $edit['pass'] = 'invalid password';
    $this->drupalPostForm('filter/tips', $edit, t('Log in'));
    $this->assertText(t('Unrecognized username or password. Forgot your password?'));
    $this->drupalGet('filter/tips');
    $this->assertNoText(t('Unrecognized username or password. Forgot your password?'));
  }

  /**
   * Updates the access column for a user.
   */
  private function updateAccess($uid, $access = REQUEST_TIME) {
    db_update('users_field_data')
      ->condition('uid', $uid)
      ->fields(['access' => $access])
      ->execute();
  }

}
