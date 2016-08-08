<?php

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\block\Entity\Block;

/**
 * Tests that an active block assigned to a non-existing region triggers the
 * warning message and is disabled.
 *
 * @group block
 */
class BlockInvalidRegionTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('block', 'block_test');

  protected function setUp() {
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
    \Drupal::configFactory()->getEditable('block.block.' . $block->id())->set('region', 'invalid_region')->save();
    $block = Block::load($block->id());

    $warning_message = t('The block %info was assigned to the invalid region %region and has been disabled.', array('%info' => $block->id(), '%region' => 'invalid_region'));

    // Clearing the cache should disable the test block placed in the invalid region.
    $this->drupalPostForm('admin/config/development/performance', array(), 'Clear all caches');
    $this->assertRaw($warning_message, 'Enabled block was in the invalid region and has been disabled.');

    // Clear the cache to check if the warning message is not triggered.
    $this->drupalPostForm('admin/config/development/performance', array(), 'Clear all caches');
    $this->assertNoRaw($warning_message, 'Disabled block in the invalid region will not trigger the warning.');

    // Place disabled test block in the invalid region of the default theme.
    \Drupal::configFactory()->getEditable('block.block.' . $block->id())->set('region', 'invalid_region')->save();
    $block = Block::load($block->id());

    // Clear the cache to check if the warning message is not triggered.
    $this->drupalPostForm('admin/config/development/performance', array(), 'Clear all caches');
    $this->assertNoRaw($warning_message, 'Disabled block in the invalid region will not trigger the warning.');
  }

}
