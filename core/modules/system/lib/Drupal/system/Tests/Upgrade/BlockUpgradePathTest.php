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

    // Add a new custom block with a title of 255 characters.
    $block_title_1 = $this->randomName(255);
    $custom_block_1 = array();
    $custom_block_1['title'] = $block_title_1;
    $custom_block_1['info'] = $this->randomName(8);
    $custom_block_1['body[value]'] = $this->randomName(32);
    $custom_block_1['regions[bartik]'] = 'sidebar_first';
    $this->drupalPost('admin/structure/block/add', $custom_block_1, t('Save block'));
    // Confirm that the custom block has been created, and title matches input.
    $this->drupalGet('');
    $this->assertText($block_title_1, 'Block with title longer than 64 characters successfully created.');

    // Add a new custom block with a title over 255 characters.
    $block_title_2 = $this->randomName(256);
    $custom_block_2 = array();
    $custom_block_2['title'] = $block_title_2;
    $custom_block_2['info'] = $this->randomName(8);
    $custom_block_2['body[value]'] = $this->randomName(32);
    $this->drupalPost('admin/structure/block/add', $custom_block_2, t('Save block'));
    // Confirm that the custom block cannot be created with title longer than
    // the maximum number of characters.
    $this->assertText('Block title cannot be longer than 255 characters', 'Block with title longer than 255 characters created unsuccessfully.');
  }

}
