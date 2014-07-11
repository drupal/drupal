<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\AccessDeniedTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

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
  public static $modules = array('block');

  protected $admin_user;

  function setUp() {
    parent::setUp();

    // Create an administrative user.
    $this->admin_user = $this->drupalCreateUser(array('access administration pages', 'administer site configuration', 'administer blocks'));

    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access user profiles'));
    user_role_grant_permissions(DRUPAL_AUTHENTICATED_RID, array('access user profiles'));
  }

  function testAccessDenied() {
    $this->drupalGet('admin');
    $this->assertText(t('Access denied'), 'Found the default 403 page');
    $this->assertResponse(403);

    // Use a custom 403 page.
    $this->drupalLogin($this->admin_user);
    $edit = array(
      'site_403' => 'user/' . $this->admin_user->id(),
    );
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Save configuration'));

    // Enable the user login block.
    $this->drupalPlaceBlock('user_login_block', array('id' => 'login'));

    // Log out and check that the user login block is shown on custom 403 pages.
    $this->drupalLogout();
    $this->drupalGet('admin');
    $this->assertText($this->admin_user->getUsername(), 'Found the custom 403 page');
    $this->assertText(t('Username'), 'Blocks are shown on the custom 403 page');

    // Log back in and remove the custom 403 page.
    $this->drupalLogin($this->admin_user);
    $edit = array(
      'site_403' => '',
    );
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Save configuration'));

    // Logout and check that the user login block is shown on default 403 pages.
    $this->drupalLogout();
    $this->drupalGet('admin');
    $this->assertText(t('Access denied'), 'Found the default 403 page');
    $this->assertResponse(403);
    $this->assertText(t('Username'), 'Blocks are shown on the default 403 page');

    // Log back in, set the custom 403 page to /user and remove the block
    $this->drupalLogin($this->admin_user);
    \Drupal::config('system.site')->set('page.403', 'user')->save();
    $edit = array(
      'region' => -1,
    );
    $this->drupalPostForm('admin/structure/block/manage/login', $edit, t('Save block'));

    // Check that we can log in from the 403 page.
    $this->drupalLogout();
    $edit = array(
      'name' => $this->admin_user->getUsername(),
      'pass' => $this->admin_user->pass_raw,
    );
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Log in'));

    // Check that we're still on the same page.
    $this->assertText(t('Site information'));
  }
}
