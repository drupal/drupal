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
    $this->drupalGet('admin/structure/block/add/system_menu_block:main/bartik');
    $this->drupalPostForm(NULL, array(
      'region' => 'sidebar_first',
      'id' => 'main_navigation',
    ), t('Save block'));
    // Verify admin user can see the block.
    $this->drupalGet('');
    $this->assertText('Main navigation');

    // Verify we have role = aria on system_powered_by and system_help_block
    // blocks.
    $this->drupalGet('admin/structure/block');
    $elements = $this->xpath('//div[@role=:role and @id=:id]', array(
      ':role' => 'complementary',
      ':id' => 'block-bartik-help',
    ));

    $this->assertEqual(count($elements), 1, 'Found complementary role on help block.');

    $this->drupalGet('');
    $elements = $this->xpath('//div[@role=:role and @id=:id]', array(
      ':role' => 'complementary',
      ':id' => 'block-bartik-powered',
    ));
    $this->assertEqual(count($elements), 1, 'Found complementary role on powered by block.');

    // Verify anonymous user can see the block.
    $this->drupalLogout();
    $this->assertText('Main navigation');

  }

}
