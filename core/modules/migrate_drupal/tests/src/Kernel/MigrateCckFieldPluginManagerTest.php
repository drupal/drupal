<?php

namespace Drupal\Tests\migrate_drupal\Kernel;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Tests the cck field plugin manager.
 *
 * @group migrate_drupal
 */
class MigrateCckFieldPluginManagerTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'migrate_drupal',
    'options',
    'file',
    'text',
    'migrate_cckfield_plugin_manager_test',
  ];

  /**
   * Tests that the correct MigrateCckField plugins are used.
   */
  public function testPluginSelection() {
    $plugin_manager = \Drupal::service('plugin.manager.migrate.cckfield');

    $this->assertSame('d6_file', $plugin_manager->getPluginIdFromFieldType('file', ['core' => 6]));

    try {
      // If this test passes, getPluginIdFromFieldType will raise a
      // PluginNotFoundException and we'll never reach fail().
      $plugin_manager->getPluginIdFromFieldType('d6_file', ['core' => 7]);
      $this->fail('Expected Drupal\Component\Plugin\Exception\PluginNotFoundException.');
    }
    catch (PluginNotFoundException $e) {
      $this->assertSame($e->getMessage(), "Plugin ID 'd6_file' was not found.");
    }

    // Test fallback when no core version is specified.
    $this->assertSame('d6_no_core_version_specified', $plugin_manager->getPluginIdFromFieldType('d6_no_core_version_specified', ['core' => 6]));

    try {
      // If this test passes, getPluginIdFromFieldType will raise a
      // PluginNotFoundException and we'll never reach fail().
      $plugin_manager->getPluginIdFromFieldType('d6_no_core_version_specified', ['core' => 7]);
      $this->fail('Expected Drupal\Component\Plugin\Exception\PluginNotFoundException.');
    }
    catch (PluginNotFoundException $e) {
      $this->assertSame($e->getMessage(), "Plugin ID 'd6_no_core_version_specified' was not found.");
    }
  }

}
