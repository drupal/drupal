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

    // Add a block instance with a 255-character title.
    $title = $this->randomName(255);
    $this->drupalPlaceBlock('system_powered_by_block', array('title' => $title));
    // Confirm that the custom block has been created, and title matches input.
    $this->drupalGet('');
    $this->assertText($title, 'Block with title longer than 64 characters successfully created.');

    // Try to add a block with a title over 255 characters.
    // WebTestBase::drupalPlaceBlock() asserts that the block is created
    // successfully. In this case we expect the block creation to fail, so
    // create a new instance of the block manually.
    $settings = array(
      'title' => $this->randomName(256),
      'machine_name' => strtolower($this->randomName(8)),
      'region' => 'sidebar_first',
    );
    $this->drupalPost('admin/structure/block/manage/system_powered_by_block/' . variable_get('theme_default', 'stark'), $settings, t('Save block'));

    // Confirm that the custom block cannot be created with title longer than
    // the maximum number of characters.
    $this->assertText('Block title cannot be longer than 255 characters');
  }

}
