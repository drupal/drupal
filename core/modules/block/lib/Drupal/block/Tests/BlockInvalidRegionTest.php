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
  public static function getInfo() {
    return array(
      'name' => 'Blocks in invalid regions',
      'description' => 'Checks that an active block assigned to a non-existing region triggers the warning message and is disabled.',
      'group' => 'Block',
    );
  }

  function setUp() {
    parent::setUp(array('block', 'block_test'));
    // Create an admin user.
    $admin_user = $this->drupalCreateUser(array('administer site configuration', 'access administration pages'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that blocks assigned to invalid regions work correctly.
   */
  function testBlockInInvalidRegion() {
    // Enable a test block in the default theme and place it in an invalid region.
    db_merge('block')
      ->key(array(
        'module' => 'block_test',
        'delta' => 'test_html_id',
        'theme' => variable_get('theme_default', 'stark'),
      ))
      ->fields(array(
        'status' => 1,
        'region' => 'invalid_region',
        'cache' => DRUPAL_NO_CACHE,
      ))
      ->execute();

    $warning_message = t('The block %info was assigned to the invalid region %region and has been disabled.', array('%info' => t('Test block html id'), '%region' => 'invalid_region'));

    // Clearing the cache should disable the test block placed in the invalid region.
    $this->drupalPost('admin/config/development/performance', array(), 'Clear all caches');
    $this->assertRaw($warning_message, 'Enabled block was in the invalid region and has been disabled.');

    // Clear the cache to check if the warning message is not triggered.
    $this->drupalPost('admin/config/development/performance', array(), 'Clear all caches');
    $this->assertNoRaw($warning_message, 'Disabled block in the invalid region will not trigger the warning.');

    // Place disabled test block in the invalid region of the default theme.
    db_merge('block')
      ->key(array(
        'module' => 'block_test',
        'delta' => 'test_html_id',
        'theme' => variable_get('theme_default', 'stark'),
      ))
      ->fields(array(
        'region' => 'invalid_region',
        'cache' => DRUPAL_NO_CACHE,
      ))
      ->execute();

    // Clear the cache to check if the warning message is not triggered.
    $this->drupalPost('admin/config/development/performance', array(), 'Clear all caches');
    $this->assertNoRaw($warning_message, 'Disabled block in the invalid region will not trigger the warning.');
  }
}
