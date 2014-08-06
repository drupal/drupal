<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\PageNotFoundTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Tests page not found functionality, including custom 404 pages.
 *
 * @group system
 */
class PageNotFoundTest extends WebTestBase {
  protected $admin_user;

  function setUp() {
    parent::setUp();

    // Create an administrative user.
    $this->admin_user = $this->drupalCreateUser(array('administer site configuration'));

    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access user profiles'));
    user_role_grant_permissions(DRUPAL_AUTHENTICATED_RID, array('access user profiles'));
  }

  function testPageNotFound() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->randomMachineName(10));
    $this->assertText(t('Page not found'), 'Found the default 404 page');

    // Use a custom 404 page.
    $edit = array(
      'site_404' => 'user/' . $this->admin_user->id(),
    );
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Save configuration'));

    $this->drupalGet($this->randomMachineName(10));
    $this->assertText($this->admin_user->getUsername(), 'Found the custom 404 page');
  }
}
