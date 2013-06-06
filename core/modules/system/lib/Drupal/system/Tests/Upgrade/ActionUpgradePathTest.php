<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Upgrade\ActionUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

/**
 * Tests the upgrade path of actions.
 */
class ActionUpgradePathTest extends UpgradePathTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Action upgrade test',
      'description' => 'Upgrade tests with action data.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    // Path to the database dump files.
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.minimal.database.php.gz',
    );
    parent::setUp();
  }

  /**
   * Tests to see if actions were upgrade.
   */
  public function testActionUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    $this->drupalGet('admin/people');
    $elements = $this->xpath('//select[@name="operation"]/option');
    $this->assertTrue(!empty($elements), 'The user actions were upgraded.');
  }

}
