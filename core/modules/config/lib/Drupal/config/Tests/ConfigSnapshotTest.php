<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigSnapshotTest.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Config\StorageComparer;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests config snapshot creation and updating.
 */
class ConfigSnapshotTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test', 'system');

  public static function getInfo() {
    return array(
      'name' => 'Snapshot functionality',
      'description' => 'Config snapshot creation and updating.',
      'group' => 'Configuration',
    );
  }

  public function setUp() {
    parent::setUp();
    $this->installSchema('system', 'config_snapshot');
  }

  /**
   * Tests config snapshot creation and updating.
   */
  function testSnapshot() {
    $active = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');
    $snapshot = $this->container->get('config.storage.snapshot');
    $config_name = 'config_test.system';
    $config_key = 'foo';
    $new_data = 'foobar';

    $active_snapshot_comparer = new StorageComparer($active, $snapshot);
    $staging_snapshot_comparer = new StorageComparer($staging, $snapshot);

    // Verify that we have an initial snapshot that matches the active
    // configuration. This has to be true as no config should be installed.
    $this->assertFalse($active_snapshot_comparer->createChangelist()->hasChanges());

    // Install the default config.
    config_install_default_config('module', 'config_test');
    // Although we have imported config this has not affected the snapshot.
    $this->assertTrue($active_snapshot_comparer->reset()->hasChanges());

    // Update the config snapshot.
    config_import_create_snapshot($active, $snapshot);

    // The snapshot and active config should now contain the same config
    // objects.
    $this->assertFalse($active_snapshot_comparer->reset()->hasChanges());

    // Change a configuration value in staging.
    $staging_data = config($config_name)->get();
    $staging_data[$config_key] = $new_data;
    $staging->write($config_name, $staging_data);

    // Verify that active and snapshot match, and that staging doesn't match
    // active.
    $this->assertFalse($active_snapshot_comparer->reset()->hasChanges());
    $this->assertTrue($staging_snapshot_comparer->createChangelist()->hasChanges());

    // Import changed data from staging to active.
    $this->configImporter()->import();

    // Verify changed config was properly imported.
    $this->assertIdentical(config($config_name)->get($config_key), $new_data);

    // Verify that a new snapshot was created which and that it matches
    // the active config.
    $this->assertFalse($active_snapshot_comparer->reset()->hasChanges());
  }

}
