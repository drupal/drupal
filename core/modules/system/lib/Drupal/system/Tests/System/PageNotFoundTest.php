<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\PageNotFoundTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Tests "404 Not found" pages and custom 404 pages.
 */
class PageNotFoundTest extends WebTestBase {
  protected $admin_user;

  public static function getInfo() {
    return array(
      'name' => '404 functionality',
      'description' => "Tests page not found functionality, including custom 404 pages.",
      'group' => 'System'
    );
  }

  function setUp() {
    parent::setUp();

    // Create an administrative user.
    $this->admin_user = $this->drupalCreateUser(array('administer site configuration'));

    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access user profiles'));
    user_role_grant_permissions(DRUPAL_AUTHENTICATED_RID, array('access user profiles'));
  }

  function testPageNotFound() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->randomName(10));
    $this->assertText(t('Page not found'), 'Found the default 404 page');

    // Use a custom 404 page.
    $edit = array(
      'site_404' => 'user/' . $this->admin_user->id(),
    );
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Save configuration'));

    $this->drupalGet($this->randomName(10));
    $this->assertText($this->admin_user->getUsername(), 'Found the custom 404 page');
  }
}
