<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\user\RoleInterface;

/**
 * Tests page not found functionality, including custom 404 pages.
 *
 * @group system
 */
class PageNotFoundTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system_test'];

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

    // Create an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'link to any page',
    ]);
    $this->adminUser->roles[] = 'administrator';
    $this->adminUser->save();

    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access user profiles']);
    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, ['access user profiles']);
  }

  public function testPageNotFound(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->randomMachineName(10));
    $this->assertSession()->pageTextContains('Page not found');

    // Set a custom 404 page without a starting slash.
    $edit = [
      'site_404' => 'user/' . $this->adminUser->id(),
    ];
    $this->drupalGet('admin/config/system/site-information');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains("The path '{$edit['site_404']}' has to start with a slash.");

    // Use a custom 404 page.
    $edit = [
      'site_404' => '/user/' . $this->adminUser->id(),
    ];
    $this->drupalGet('admin/config/system/site-information');
    $this->submitForm($edit, 'Save configuration');

    $this->drupalGet($this->randomMachineName(10));
    $this->assertSession()->pageTextContains($this->adminUser->getAccountName());
  }

  /**
   * Tests that an inaccessible custom 404 page falls back to the default.
   */
  public function testPageNotFoundCustomPageWithAccessDenied(): void {
    // Sets up a 404 page not accessible by the anonymous user.
    $this->config('system.site')->set('page.404', '/system-test/custom-4xx')->save();

    $this->drupalGet('/this-path-does-not-exist');
    $this->assertSession()->pageTextNotContains('Admin-only 4xx response');
    $this->assertSession()->pageTextContains('The requested page could not be found.');
    $this->assertSession()->statusCodeEquals(404);
    // Verify the access cacheability metadata for custom 404 is bubbled.
    $this->assertCacheContext('user.roles');

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/this-path-does-not-exist');
    $this->assertSession()->pageTextContains('Admin-only 4xx response');
    $this->assertSession()->pageTextNotContains('The requested page could not be found.');
    $this->assertSession()->statusCodeEquals(404);
    // Verify the access cacheability metadata for custom 404 is bubbled.
    $this->assertCacheContext('user.roles');
  }

}
