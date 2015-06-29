<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\PageNotFoundTest.
 */

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
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    // Create an administrative user.
    $this->adminUser = $this->drupalCreateUser(array('administer site configuration', 'link to any page'));

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
    $this->assertRaw(SafeMarkup::format("The path '%path' has to start with a slash.", ['%path' =>  $edit['site_404']]));

    // Use a custom 404 page.
    $edit = array(
      'site_404' => '/user/' . $this->adminUser->id(),
    );
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Save configuration'));

    $this->drupalGet($this->randomMachineName(10));
    $this->assertText($this->adminUser->getUsername(), 'Found the custom 404 page');
  }
}
