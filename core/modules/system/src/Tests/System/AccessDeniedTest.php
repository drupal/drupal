<?php

namespace Drupal\system\Tests\System;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\simpletest\WebTestBase;
use Drupal\user\RoleInterface;

/**
 * Tests page access denied functionality, including custom 403 pages.
 *
 * @group system
 */
class AccessDeniedTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'node', 'system_test'];

  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('page_title_block');

    // Create an administrative user.
    $this->adminUser = $this->drupalCreateUser(['access administration pages', 'administer site configuration', 'link to any page', 'administer blocks']);
    $this->adminUser->roles[] = 'administrator';
    $this->adminUser->save();

    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access user profiles']);
    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, ['access user profiles']);
  }

  public function testAccessDenied() {
    $this->drupalGet('admin');
    $this->assertText(t('Access denied'), 'Found the default 403 page');
    $this->assertResponse(403);

    // Ensure that users without permission are denied access and have the
    // correct path information in drupalSettings.
    $this->drupalLogin($this->createUser([]));
    $this->drupalGet('admin', ['query' => ['foo' => 'bar']]);
    $this->assertEqual($this->drupalSettings['path']['currentPath'], 'admin');
    $this->assertEqual($this->drupalSettings['path']['currentPathIsAdmin'], TRUE);
    $this->assertEqual($this->drupalSettings['path']['currentQuery'], ['foo' => 'bar']);

    $this->drupalLogin($this->adminUser);

    // Set a custom 404 page without a starting slash.
    $edit = [
      'site_403' => 'user/' . $this->adminUser->id(),
    ];
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Save configuration'));
    $this->assertRaw(SafeMarkup::format("The path '%path' has to start with a slash.", ['%path' => $edit['site_403']]));

    // Use a custom 403 page.
    $edit = [
      'site_403' => '/user/' . $this->adminUser->id(),
    ];
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Save configuration'));

    // Enable the user login block.
    $block = $this->drupalPlaceBlock('user_login_block', ['id' => 'login']);

    // Log out and check that the user login block is shown on custom 403 pages.
    $this->drupalLogout();
    $this->drupalGet('admin');
    $this->assertText($this->adminUser->getUsername(), 'Found the custom 403 page');
    $this->assertText(t('Username'), 'Blocks are shown on the custom 403 page');

    // Log back in and remove the custom 403 page.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'site_403' => '',
    ];
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Save configuration'));

    // Logout and check that the user login block is shown on default 403 pages.
    $this->drupalLogout();
    $this->drupalGet('admin');
    $this->assertText(t('Access denied'), 'Found the default 403 page');
    $this->assertResponse(403);
    $this->assertText(t('Username'), 'Blocks are shown on the default 403 page');

    // Log back in, set the custom 403 page to /user/login and remove the block
    $this->drupalLogin($this->adminUser);
    $this->config('system.site')->set('page.403', '/user/login')->save();
    $block->disable()->save();

    // Check that we can log in from the 403 page.
    $this->drupalLogout();
    $edit = [
      'name' => $this->adminUser->getUsername(),
      'pass' => $this->adminUser->pass_raw,
    ];
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Log in'));

    // Check that we're still on the same page.
    $this->assertText(t('Basic site settings'));
  }

  /**
   * Tests that an inaccessible custom 403 page falls back to the default.
   */
  public function testAccessDeniedCustomPageWithAccessDenied() {
    // Sets up a 403 page not accessible by the anonymous user.
    $this->config('system.site')->set('page.403', '/system-test/custom-4xx')->save();

    $this->drupalGet('/system-test/always-denied');
    $this->assertNoText('Admin-only 4xx response');
    $this->assertText('You are not authorized to access this page.');
    $this->assertResponse(403);
    // Verify the access cacheability metadata for custom 403 is bubbled.
    $this->assertCacheContext('user.roles');

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/system-test/always-denied');
    $this->assertText('Admin-only 4xx response');
    $this->assertResponse(403);
    // Verify the access cacheability metadata for custom 403 is bubbled.
    $this->assertCacheContext('user.roles');
  }

}
