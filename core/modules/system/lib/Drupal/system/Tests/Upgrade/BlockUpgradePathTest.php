<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Upgrade\BlockUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

/**
 * Tests upgrading a bare database.
 *
 * Loads a bare installation of Drupal 7 and runs the upgrade process on it.
 */
class BlockUpgradePathTest extends UpgradePathTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Block upgrade test',
      'description' => 'Upgrade tests with block data.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.minimal.database.php.gz',
    );
    parent::setUp();
  }

  /**
   * Tests block title length after successful upgrade.
   */
  public function testBlockUpgradeTitleLength() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // WebTestBase::drupalPlaceBlock() uses the API directly, which doesn't
    // output validation errors or success messages, so create the blocks from
    // the UI.

    // Add a block instance with a 255-character title.
    // Confirm that the custom block has been created, and title matches input.
    $settings = array(
      'settings[label]' => $this->randomName(255),
      'id' => strtolower($this->randomName(8)),
      'region' => 'sidebar_first',
    );
    $this->drupalPostForm('admin/structure/block/add/system_powered_by_block/' . \Drupal::config('system.theme')->get('default'), $settings, t('Save block'));
    $this->assertText($settings['settings[label]'], 'Block with title longer than 64 characters successfully created.');

    // Try to add a block with a title over 255 characters.
    $settings = array(
      'settings[label]' => $this->randomName(256),
      'id' => strtolower($this->randomName(8)),
      'region' => 'sidebar_first',
    );
    $this->drupalPostForm('admin/structure/block/add/system_powered_by_block/' . \Drupal::config('system.theme')->get('default'), $settings, t('Save block'));

    // Confirm that the custom block cannot be created with title longer than
    // the maximum number of characters.
    $this->assertText('Title cannot be longer than 255 characters');
  }

}
