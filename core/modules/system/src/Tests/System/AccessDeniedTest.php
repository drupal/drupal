<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\AccessDeniedTest.
 */

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
  public static $modules = ['block'];

  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('page_title_block');

    // Create an administrative user.
    $this->adminUser = $this->drupalCreateUser(['access administration pages', 'administer site configuration', 'link to any page', 'administer blocks']);

    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, array('access user profiles'));
    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, array('access user profiles'));
  }

  function testAccessDenied() {
    $this->drupalGet('admin');
    $this->assertText(t('Access denied'), 'Found the default 403 page');
    $this->assertResponse(403);

    $this->drupalLogin($this->adminUser);

    // Set a custom 404 page without a starting slash.
    $edit = [
      'site_403' => 'user/' . $this->adminUser->id(),
    ];
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Save configuration'));
    $this->assertRaw(SafeMarkup::format("The path '%path' has to start with a slash.", ['%path' =>  $edit['site_403']]));

    // Use a custom 403 page.
    $edit = [
      'site_403' => '/user/' . $this->adminUser->id(),
    ];
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Save configuration'));

    // Enable the user login block.
    $this->drupalPlaceBlock('user_login_block', array('id' => 'login'));

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
    $edit = [
      'region' => -1,
    ];
    $this->drupalPostForm('admin/structure/block/manage/login', $edit, t('Save block'));

    // Check that we can log in from the 403 page.
    $this->drupalLogout();
    $edit = array(
      'name' => $this->adminUser->getUsername(),
      'pass' => $this->adminUser->pass_raw,
    );
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Log in'));

    // Check that we're still on the same page.
    $this->assertText(t('Site information'));
  }
}
