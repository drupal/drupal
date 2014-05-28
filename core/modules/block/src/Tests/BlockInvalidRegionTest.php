<?php

/**
 * @file
 * Definition of Drupal\block\Tests\BlockInvalidRegionTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that a block assigned to an invalid region triggers the warning.
 */
class BlockInvalidRegionTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'block_test');

  public static function getInfo() {
    return array(
      'name' => 'Blocks in invalid regions',
      'description' => 'Checks that an active block assigned to a non-existing region triggers the warning message and is disabled.',
      'group' => 'Block',
    );
  }

  function setUp() {
    parent::setUp();
    // Create an admin user.
    $admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
      'access administration pages',
      'administer blocks',
    ));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that blocks assigned to invalid regions work correctly.
   */
  function testBlockInInvalidRegion() {
    // Enable a test block and place it in an invalid region.
    $block = $this->drupalPlaceBlock('test_html');
    $block->set('region', 'invalid_region');
    $block->save();

    $warning_message = t('The block %info was assigned to the invalid region %region and has been disabled.', array('%info' => $block->id(), '%region' => 'invalid_region'));

    // Clearing the cache should disable the test block placed in the invalid region.
    $this->drupalPostForm('admin/config/development/performance', array(), 'Clear all caches');
    $this->assertRaw($warning_message, 'Enabled block was in the invalid region and has been disabled.');

    // Clear the cache to check if the warning message is not triggered.
    $this->drupalPostForm('admin/config/development/performance', array(), 'Clear all caches');
    $this->assertNoRaw($warning_message, 'Disabled block in the invalid region will not trigger the warning.');

    // Place disabled test block in the invalid region of the default theme.
    $block = entity_load('block', $block->id());
    $block->set('region', 'invalid_region');
    $block->save();

    // Clear the cache to check if the warning message is not triggered.
    $this->drupalPostForm('admin/config/development/performance', array(), 'Clear all caches');
    $this->assertNoRaw($warning_message, 'Disabled block in the invalid region will not trigger the warning.');
  }
}
