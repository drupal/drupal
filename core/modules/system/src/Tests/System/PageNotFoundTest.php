<?php

namespace Drupal\system\Tests\System;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\simpletest\WebTestBase;
use Drupal\user\RoleInterface;

/**
 * Tests page not found functionality, including custom 404 pages.
 *
 * @group system
 */
class PageNotFoundTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system_test'];

  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    // Create an administrative user.
    $this->adminUser = $this->drupalCreateUser(array('administer site configuration', 'link to any page'));
    $this->adminUser->roles[] = 'administrator';
    $this->adminUser->save();

    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, array('access user profiles'));
    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, array('access user profiles'));
  }

  function testPageNotFound() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->randomMachineName(10));
    $this->assertText(t('Page not found'), 'Found the default 404 page');

    // Set a custom 404 page without a starting slash.
    $edit = [
      'site_404' => 'user/' . $this->adminUser->id(),
    ];
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Save configuration'));
    $this->assertRaw(SafeMarkup::format("The path '%path' has to start with a slash.", ['%path' => $edit['site_404']]));

    // Use a custom 404 page.
    $edit = array(
      'site_404' => '/user/' . $this->adminUser->id(),
    );
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Save configuration'));

    $this->drupalGet($this->randomMachineName(10));
    $this->assertText($this->adminUser->getUsername(), 'Found the custom 404 page');
  }

  /**
   * Tests that an inaccessible custom 404 page falls back to the default.
   */
  public function testPageNotFoundCustomPageWithAccessDenied() {
    // Sets up a 404 page not accessible by the anonymous user.
    $this->config('system.site')->set('page.404', '/system-test/custom-4xx')->save();

    $this->drupalGet('/this-path-does-not-exist');
    $this->assertNoText('Admin-only 4xx response');
    $this->assertText('The requested page could not be found.');
    $this->assertResponse(404);
    // Verify the access cacheability metadata for custom 404 is bubbled.
    $this->assertCacheContext('user.roles');

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/this-path-does-not-exist');
    $this->assertText('Admin-only 4xx response');
    $this->assertNoText('The requested page could not be found.');
    $this->assertResponse(404);
    // Verify the access cacheability metadata for custom 404 is bubbled.
    $this->assertCacheContext('user.roles');
  }

}
