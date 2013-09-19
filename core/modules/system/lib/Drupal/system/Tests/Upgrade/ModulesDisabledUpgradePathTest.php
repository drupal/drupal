<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Upgrade\ModulesDisabledUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

/**
 * Tests upgrading with all non-required modules installed but disabled.
 *
 * Loads a filled installation of Drupal 7 with disabled modules and runs the
 * upgrade process on it.
 */
class ModulesDisabledUpgradePathTest extends UpgradePathTestBase {
  public static function getInfo() {
    return array(
      'name'  => 'Modules disabled upgrade test',
      'description'  => 'Upgrade path test for disabled modules.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.filled.standard_all.database.php.gz',
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.all-disabled.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests an upgrade with all non-required modules installed but disabled.
   */
  public function testDisabledUpgrade() {
    $modules = db_query('SELECT name, info FROM {system} WHERE type = :module AND status = 0 AND schema_version <> :schema_uninstalled', array(
      ':module' => 'module',
      ':schema_uninstalled' => SCHEMA_UNINSTALLED,
    ))->fetchAllKeyed(0, 1);
    array_walk($modules, function (&$value, $key) {
      $info = unserialize($value);
      $value = $info['name'];
    });
    // Load the first update screen.
    $this->getUpdatePhp();
    if (!$this->assertResponse(200)) {
      throw new \Exception('Initial GET to update.php did not return HTTP 200 status.');
    }
    $this->assertNoFieldByXPath('//input[@type="submit"]', NULL, 'No continue button found on update.php.');
    $this->assertText('Drupal 8 no longer supports disabled modules. Please either enable or uninstall them before upgrading.');
    $this->assertText(implode(', ', $modules));
  }
}
