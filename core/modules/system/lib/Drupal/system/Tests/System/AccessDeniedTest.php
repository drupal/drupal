<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\AccessDeniedTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Tests custom access denied functionality.
 */
class AccessDeniedTest extends WebTestBase {
  protected $admin_user;

  public static function getInfo() {
    return array(
      'name' => '403 functionality',
      'description' => 'Tests page access denied functionality, including custom 403 pages.',
      'group' => 'System'
    );
  }

  function setUp() {
    parent::setUp(array('block'));

    // Create an administrative user.
    $this->admin_user = $this->drupalCreateUser(array('access administration pages', 'administer site configuration', 'administer blocks'));

    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access user profiles'));
    user_role_grant_permissions(DRUPAL_AUTHENTICATED_RID, array('access user profiles'));
  }

  function testAccessDenied() {
    $this->drupalGet('admin');
    $this->assertText(t('Access denied'), t('Found the default 403 page'));
    $this->assertResponse(403);

    // Use a custom 403 page.
    $this->drupalLogin($this->admin_user);
    $edit = array(
      'site_403' => 'user/' . $this->admin_user->uid,
    );
    $this->drupalPost('admin/config/system/site-information', $edit, t('Save configuration'));

    // Enable the user login block.
    $edit = array(
      'blocks[user_login][region]' => 'sidebar_first',
    );
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));

    // Logout and check that the user login block is shown on custom 403 pages.
    $this->drupalLogout();
    $this->drupalGet('admin');
    $this->assertText($this->admin_user->name, t('Found the custom 403 page'));
    $this->assertText(t('User login'), t('Blocks are shown on the custom 403 page'));

    // Log back in and remove the custom 403 page.
    $this->drupalLogin($this->admin_user);
    $edit = array(
      'site_403' => '',
    );
    $this->drupalPost('admin/config/system/site-information', $edit, t('Save configuration'));

    // Logout and check that the user login block is shown on default 403 pages.
    $this->drupalLogout();
    $this->drupalGet('admin');
    $this->assertText(t('Access denied'), t('Found the default 403 page'));
    $this->assertResponse(403);
    $this->assertText(t('User login'), t('Blocks are shown on the default 403 page'));

    // Log back in, set the custom 403 page to /user and remove the block
    $this->drupalLogin($this->admin_user);
    config('system.site')->set('page.403', 'user')->save();
    $this->drupalPost('admin/structure/block', array('blocks[user_login][region]' => '-1'), t('Save blocks'));

    // Check that we can log in from the 403 page.
    $this->drupalLogout();
    $edit = array(
      'name' => $this->admin_user->name,
      'pass' => $this->admin_user->pass_raw,
    );
    $this->drupalPost('admin/config/system/site-information', $edit, t('Log in'));

    // Check that we're still on the same page.
    $this->assertText(t('Site information'));
  }
}
