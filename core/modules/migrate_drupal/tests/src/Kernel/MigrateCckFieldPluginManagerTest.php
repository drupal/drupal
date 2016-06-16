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
  public static $modules = array('system', 'user', 'field', 'migrate_drupal', 'options', 'file', 'text', 'migrate_cckfield_plugin_manager_test');

  /**
   * Tests that the correct MigrateCckField plugins are used.
   */
  public function testPluginSelection() {
    $plugin_manager = \Drupal::service('plugin.manager.migrate.cckfield');

    $this->assertIdentical('Drupal\\file\\Plugin\\migrate\\cckfield\\d6\\FileField', get_class($plugin_manager->createInstance('filefield', ['core' => 6])));

    try {
      // If this test passes, createInstance will raise a
      // PluginNotFoundException and we'll never reach fail().
      $plugin_manager->createInstance('filefield', ['core' => 7]);
      $this->fail('Expected Drupal\Component\Plugin\Exception\PluginNotFoundException.');
    }
    catch (PluginNotFoundException $e) {
      $this->assertIdentical($e->getMessage(), "Plugin ID 'filefield' was not found.");
    }

    $this->assertIdentical('Drupal\\file\\Plugin\\migrate\\cckfield\\d7\\ImageField', get_class($plugin_manager->createInstance('image', ['core' => 7])));
    $this->assertIdentical('Drupal\\file\\Plugin\\migrate\\cckfield\\d7\\FileField', get_class($plugin_manager->createInstance('file', ['core' => 7])));
    $this->assertIdentical('Drupal\\migrate_cckfield_plugin_manager_test\\Plugin\\migrate\\cckfield\\D6FileField', get_class($plugin_manager->createInstance('file', ['core' => 6])));

    $this->assertIdentical('Drupal\\text\\Plugin\\migrate\\cckfield\\TextField', get_class($plugin_manager->createInstance('text', ['core' => 6])));
    $this->assertIdentical('Drupal\\text\\Plugin\\migrate\\cckfield\\TextField', get_class($plugin_manager->createInstance('text', ['core' => 7])));

    // Test fallback when no core version is specified.
    $this->assertIdentical('Drupal\\migrate_cckfield_plugin_manager_test\\Plugin\\migrate\\cckfield\\D6NoCoreVersionSpecified', get_class($plugin_manager->createInstance('d6_no_core_version_specified', ['core' => 6])));

    try {
      // If this test passes, createInstance will raise a
      // PluginNotFoundException and we'll never reach fail().
      $plugin_manager->createInstance('d6_no_core_version_specified', ['core' => 7]);
      $this->fail('Expected Drupal\Component\Plugin\Exception\PluginNotFoundException.');
    }
    catch (PluginNotFoundException $e) {
      $this->assertIdentical($e->getMessage(), "Plugin ID 'd6_no_core_version_specified' was not found.");
    }
  }

}
