<?php

/**
 * @file
 * Contains Drupal\standard\Tests\StandardTest.
 */

namespace Drupal\standard\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests Standard installation profile expectations.
 */
class StandardTest extends WebTestBase {

  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'Standard installation profile',
      'description' => 'Tests Standard installation profile expectations.',
      'group' => 'Standard',
    );
  }

  /**
   * Tests Standard installation profile.
   */
  function testStandard() {
    $this->drupalGet('');
    $this->assertLink(t('Contact'));
    $this->clickLink(t('Contact'));
    $this->assertResponse(200);

    // Test anonymous user can access 'Main navigation' block.
    $admin = $this->drupalCreateUser(array('administer blocks'));
    $this->drupalLogin($admin);
    // Configure the block.
    $this->drupalGet('admin/structure/block/add/system_menu_block:menu-main/bartik');
    $this->drupalPost(NULL, array(
      'region' => 'sidebar_first',
      'machine_name' => 'main_navigation',
    ), t('Save block'));
    // Verify admin user can see the block.
    $this->drupalGet('');
    $this->assertText('Main navigation');
    // Verify anonymous user can see the block.
    $this->drupalLogout();
    $this->assertText('Main navigation');
  }

}
