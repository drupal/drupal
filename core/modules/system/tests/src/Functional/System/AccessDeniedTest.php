<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\user\RoleInterface;

/**
 * Tests page access denied functionality, including custom 403 pages.
 *
 * @group system
 */
class AccessDeniedTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'node', 'system_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('page_title_block');

    // Create an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
      'link to any page',
      'administer blocks',
    ]);
    $this->adminUser->roles[] = 'administrator';
    $this->adminUser->save();

    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access user profiles']);
    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, ['access user profiles']);
  }

  public function testAccessDenied() {
    $this->drupalGet('admin');
    $this->assertSession()->pageTextContains('Access denied');
    $this->assertSession()->statusCodeEquals(403);

    // Ensure that users without permission are denied access and have the
    // correct path information in drupalSettings.
    $this->drupalLogin($this->createUser([]));
    $this->drupalGet('admin', ['query' => ['foo' => 'bar']]);

    $settings = $this->getDrupalSettings();
    $this->assertEquals('admin', $settings['path']['currentPath']);
    $this->assertTrue($settings['path']['currentPathIsAdmin']);
    $this->assertEquals(['foo' => 'bar'], $settings['path']['currentQuery']);

    $this->drupalLogin($this->adminUser);

    // Set a custom 404 page without a starting slash.
    $edit = [
      'site_403' => 'user/' . $this->adminUser->id(),
    ];
    $this->drupalGet('admin/config/system/site-information');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains("The path '{$edit['site_403']}' has to start with a slash.");

    // Use a custom 403 page.
    $edit = [
      'site_403' => '/user/' . $this->adminUser->id(),
    ];
    $this->drupalGet('admin/config/system/site-information');
    $this->submitForm($edit, 'Save configuration');

    // Enable the user login block.
    $block = $this->drupalPlaceBlock('user_login_block', ['id' => 'login']);

    // Log out and check that the user login block is shown on custom 403 pages.
    $this->drupalLogout();
    $this->drupalGet('admin');
    $this->assertSession()->pageTextContains($this->adminUser->getAccountName());
    $this->assertSession()->pageTextContains('Username');

    // Log back in and remove the custom 403 page.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'site_403' => '',
    ];
    $this->drupalGet('admin/config/system/site-information');
    $this->submitForm($edit, 'Save configuration');

    // Logout and check that the user login block is shown on default 403 pages.
    $this->drupalLogout();
    $this->drupalGet('admin');
    $this->assertSession()->pageTextContains('Access denied');
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->pageTextContains('Username');

    // Log back in, set the custom 403 page to /user/login and remove the block
    $this->drupalLogin($this->adminUser);
    $this->config('system.site')->set('page.403', '/user/login')->save();
    $block->disable()->save();

    // Check that we can log in from the 403 page.
    $this->drupalLogout();
    $edit = [
      'name' => $this->adminUser->getAccountName(),
      'pass' => $this->adminUser->pass_raw,
    ];
    $this->drupalGet('admin/config/system/site-information');
    $this->submitForm($edit, 'Log in');

    // Check that we're still on the same page.
    $this->assertSession()->pageTextContains('Basic site settings');
  }

  /**
   * Tests that an inaccessible custom 403 page falls back to the default.
   */
  public function testAccessDeniedCustomPageWithAccessDenied() {
    // Sets up a 403 page not accessible by the anonymous user.
    $this->config('system.site')->set('page.403', '/system-test/custom-4xx')->save();

    $this->drupalGet('/system-test/always-denied');
    $this->assertSession()->pageTextNotContains('Admin-only 4xx response');
    $this->assertSession()->pageTextContains('You are not authorized to access this page.');
    $this->assertSession()->statusCodeEquals(403);
    // Verify the access cacheability metadata for custom 403 is bubbled.
    $this->assertCacheContext('user.roles');

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/system-test/always-denied');
    $this->assertSession()->pageTextContains('Admin-only 4xx response');
    $this->assertSession()->statusCodeEquals(403);
    // Verify the access cacheability metadata for custom 403 is bubbled.
    $this->assertCacheContext('user.roles');
  }

}
