<?php

namespace Drupal\Tests\migrate_drupal\Kernel;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Tests the field plugin manager.
 *
 * @group migrate_drupal
 */
class MigrateFieldPluginManagerTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'user', 'field', 'migrate_drupal', 'options', 'file', 'text', 'link', 'migrate_field_plugin_manager_test'];

  /**
   * Tests that the correct MigrateField plugins are used.
   */
  public function testPluginSelection() {
    $plugin_manager = $this->container->get('plugin.manager.migrate.field');

    try {
      // If this test passes, getPluginIdFromFieldType will raise a
      // PluginNotFoundException and we'll never reach fail().
      $plugin_manager->getPluginIdFromFieldType('filefield', ['core' => 7]);
      $this->fail('Expected Drupal\Component\Plugin\Exception\PluginNotFoundException.');
    }
    catch (PluginNotFoundException $e) {
      $this->assertIdentical($e->getMessage(), "Plugin ID 'filefield' was not found.");
    }

    $this->assertIdentical('link', $plugin_manager->getPluginIdFromFieldType('link', ['core' => 6]));
    $this->assertIdentical('link_field', $plugin_manager->getPluginIdFromFieldType('link_field', ['core' => 7]));
    $this->assertIdentical('image', $plugin_manager->getPluginIdFromFieldType('image', ['core' => 7]));
    $this->assertIdentical('file', $plugin_manager->getPluginIdFromFieldType('file', ['core' => 7]));
    $this->assertIdentical('d6_file', $plugin_manager->getPluginIdFromFieldType('file', ['core' => 6]));
    $this->assertIdentical('d6_text', $plugin_manager->getPluginIdFromFieldType('text', ['core' => 6]));
    $this->assertIdentical('d7_text', $plugin_manager->getPluginIdFromFieldType('text', ['core' => 7]));

    // Test fallback when no core version is specified.
    $this->assertIdentical('d6_no_core_version_specified', $plugin_manager->getPluginIdFromFieldType('d6_no_core_version_specified', ['core' => 6]));

    try {
      // If this test passes, getPluginIdFromFieldType will raise a
      // PluginNotFoundException and we'll never reach fail().
      $plugin_manager->getPluginIdFromFieldType('d6_no_core_version_specified', ['core' => 7]);
      $this->fail('Expected Drupal\Component\Plugin\Exception\PluginNotFoundException.');
    }
    catch (PluginNotFoundException $e) {
      $this->assertIdentical($e->getMessage(), "Plugin ID 'd6_no_core_version_specified' was not found.");
    }
  }

}
