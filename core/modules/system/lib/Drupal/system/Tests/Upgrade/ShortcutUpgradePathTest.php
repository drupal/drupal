<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Upgrade\ShortcutUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

use Drupal\system\Tests\Upgrade\UpgradePathTestBase;

/**
 * Tests upgrade of shortcut.
 */
class ShortcutUpgradePathTest extends UpgradePathTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Shortcut upgrade test',
      'description' => 'Tests upgrade of shortcut to the configuration system.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.standard_all.database.php.gz',
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.shortcut.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests upgrade of {shortcut_set} table to configuration entities.
   */
  public function testContactUpgrade() {
    $this->assertTrue(db_table_exists('shortcut_set'), 'Table {shortcut_set} exists.');
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');
    $this->assertFalse(db_table_exists('shortcut_set'), 'Table {shortcut_set} has been deleted.');

    // Ensure that the Drupal 7 default set has been created.
    $set = entity_load('shortcut', 'default');
    $this->assertTrue($set->uuid(), 'Converted set has a UUID');
    $this->assertEqual($set->label(), 'Default');

    // Test that the custom set has been updated.
    $set = entity_load('shortcut', 'shortcut-set-2');
    $this->assertTrue($set->uuid(), 'Converted set has a UUID');
    $this->assertEqual($set->label(), 'Custom shortcut set');
  }
}

