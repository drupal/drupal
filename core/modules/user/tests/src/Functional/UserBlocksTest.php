<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Core\Url;
use Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests user blocks.
 *
 * @group user
 */
class UserBlocksTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * A user with the 'administer blocks' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp(): void {
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
      if ($expected_visibility) {
        $this->assertSession()->elementExists('xpath', '//div[contains(@class,"block-user-login-block") and @role="form"]');
      }
      else {
        $this->assertSession()->elementNotExists('xpath', '//div[contains(@class,"block-user-login-block") and @role="form"]');
      }
    }
  }

  /**
   * Tests the user login block.
   */
  public function testUserLoginBlock() {
    // Create a user with some permission that anonymous users lack.
    $user = $this->drupalCreateUser(['administer permissions']);

    // Log in using the block.
    $edit = [];
    $edit['name'] = $user->getAccountName();
    $edit['pass'] = $user->passRaw;
    $this->drupalGet('admin/people/permissions');
    $this->submitForm($edit, 'Log in');
    $this->assertNoText('User login');

    // Check that we are still on the same page.
    $this->assertSession()->addressEquals(Url::fromRoute('user.admin_permissions'));

    // Now, log out and repeat with a non-403 page.
    $this->drupalLogout();
    $this->drupalGet('filter/tips');
    $this->assertSession()->responseHeaderEquals(DynamicPageCacheSubscriber::HEADER, 'MISS');
    $this->submitForm($edit, 'Log in');
    $this->assertNoText('User login');
    // Verify that we are still on the same page after login for allowed page.
    $this->assertSession()->responseMatches('!<title.*?Compose tips.*?</title>!');

    // Log out again and repeat with a non-403 page including query arguments.
    $this->drupalLogout();
    $this->drupalGet('filter/tips', ['query' => ['foo' => 'bar']]);
    $this->assertSession()->responseHeaderEquals(DynamicPageCacheSubscriber::HEADER, 'HIT');
    $this->submitForm($edit, 'Log in');
    $this->assertNoText('User login');
    // Verify that we are still on the same page after login for allowed page.
    $this->assertSession()->responseMatches('!<title.*?Compose tips.*?</title>!');
    $this->assertStringContainsString('/filter/tips?foo=bar', $this->getUrl(), 'Correct query arguments are displayed after login');

    // Repeat with different query arguments.
    $this->drupalLogout();
    $this->drupalGet('filter/tips', ['query' => ['foo' => 'baz']]);
    $this->assertSession()->responseHeaderEquals(DynamicPageCacheSubscriber::HEADER, 'HIT');
    $this->submitForm($edit, 'Log in');
    $this->assertNoText('User login');
    // Verify that we are still on the same page after login for allowed page.
    $this->assertSession()->responseMatches('!<title.*?Compose tips.*?</title>!');
    $this->assertStringContainsString('/filter/tips?foo=baz', $this->getUrl(), 'Correct query arguments are displayed after login');

    // Check that the user login block is not vulnerable to information
    // disclosure to third party sites.
    $this->drupalLogout();
    $this->drupalGet('http://example.com/', ['external' => FALSE]);
    $this->submitForm($edit, 'Log in');
    // Check that we remain on the site after login.
    $this->assertSession()->addressEquals($user->toUrl('canonical'));

    // Verify that form validation errors are displayed immediately for forms
    // in blocks and not on subsequent page requests.
    $this->drupalLogout();
    $edit = [];
    $edit['name'] = 'foo';
    $edit['pass'] = 'invalid password';
    $this->drupalGet('filter/tips');
    $this->submitForm($edit, 'Log in');
    $this->assertSession()->pageTextContains('Unrecognized username or password. Forgot your password?');
    $this->drupalGet('filter/tips');
    $this->assertNoText('Unrecognized username or password. Forgot your password?');
  }

}
