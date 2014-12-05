<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigSnapshotTest.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Config\StorageComparer;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests config snapshot creation and updating.
 *
 * @group config
 */
class ConfigSnapshotTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test', 'system');

  protected function setUp() {
    parent::setUp();
    // Update the config snapshot. This allows the parent::setUp() to write
    // configuration files.
    \Drupal::service('config.manager')->createSnapshot(\Drupal::service('config.storage'), \Drupal::service('config.storage.snapshot'));
    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.staging'));
  }

  /**
   * Tests config snapshot creation and updating.
   */
  function testSnapshot() {
    $active = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');
    $snapshot = $this->container->get('config.storage.snapshot');
    $config_manager = $this->container->get('config.manager');
    $config_name = 'config_test.system';
    $config_key = 'foo';
    $new_data = 'foobar';

    $active_snapshot_comparer = new StorageComparer($active, $snapshot, $config_manager);
    $staging_snapshot_comparer = new StorageComparer($staging, $snapshot, $config_manager);

    // Verify that we have an initial snapshot that matches the active
    // configuration. This has to be true as no config should be installed.
    $this->assertFalse($active_snapshot_comparer->createChangelist()->hasChanges());

    // Install the default config.
    $this->installConfig(array('config_test'));
    // Although we have imported config this has not affected the snapshot.
    $this->assertTrue($active_snapshot_comparer->reset()->hasChanges());

    // Update the config snapshot.
    \Drupal::service('config.manager')->createSnapshot($active, $snapshot);

    // The snapshot and active config should now contain the same config
    // objects.
    $this->assertFalse($active_snapshot_comparer->reset()->hasChanges());

    // Change a configuration value in staging.
    $staging_data = \Drupal::config($config_name)->get();
    $staging_data[$config_key] = $new_data;
    $staging->write($config_name, $staging_data);

    // Verify that active and snapshot match, and that staging doesn't match
    // active.
    $this->assertFalse($active_snapshot_comparer->reset()->hasChanges());
    $this->assertTrue($staging_snapshot_comparer->createChangelist()->hasChanges());

    // Import changed data from staging to active.
    $this->configImporter()->import();

    // Verify changed config was properly imported.
    \Drupal::configFactory()->reset($config_name);
    $this->assertIdentical(\Drupal::config($config_name)->get($config_key), $new_data);

    // Verify that a new snapshot was created which and that it matches
    // the active config.
    $this->assertFalse($active_snapshot_comparer->reset()->hasChanges());
  }

}
