<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldDefinitionIntegrityTest.
 */

namespace Drupal\field\Tests;

use Drupal\Core\Extension\Extension;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests the integrity of field API plugin definitions.
 *
 * @group field
 */
class FieldDefinitionIntegrityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Enable all core modules that provide field plugins.
    $modules = system_rebuild_module_data();
    $modules = array_filter($modules, function (Extension $module) {
      // Filter contrib, hidden, already enabled modules and modules in the
      // Testing package.
      if ($module->origin === 'core'
        && empty($module->info['hidden'])
        && $module->status == FALSE
        && $module->info['package'] !== 'Testing'
        && is_readable($module->getPath() . '/src/Plugin/Field')) {
        return TRUE;
      }
      return FALSE;
    });
    $this->enableModules(array_keys($modules));
  }

  /**
   * Tests the integrity of field plugin definitions.
   */
  public function testFieldPluginDefinitionIntegrity() {
    // Load the IDs of all available field type plugins.
    $available_field_type_ids = [];
    /** @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface $field_type_manager */
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    foreach ($field_type_manager->getDefinitions() as $definition) {
      $available_field_type_ids[] = $definition['id'];
    }

    // Test the field widget plugins.
    /** @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface $field_widget_manager */
    $field_widget_manager = \Drupal::service('plugin.manager.field.widget');
    foreach ($field_widget_manager->getDefinitions() as $definition) {
      $missing_field_type_ids = array_diff($definition['field_types'], $available_field_type_ids);
      if ($missing_field_type_ids) {
        $this->fail(sprintf('Field widget %s integrates with non-existent field types: %s', $definition['id'], implode(', ', $missing_field_type_ids)));
      }
      else {
        $this->pass(sprintf('Field widget %s integrates with existing field types.', $definition['id']));
      }
    }

    // Test the field formatter plugins.
    /** @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface $field_formatter_manager */
    $field_formatter_manager = \Drupal::service('plugin.manager.field.formatter');
    foreach ($field_formatter_manager->getDefinitions() as $definition) {
      $missing_field_type_ids = array_diff($definition['field_types'], $available_field_type_ids);
      if ($missing_field_type_ids) {
        $this->fail(sprintf('Field formatter %s integrates with non-existent field types: %s', $definition['id'], implode(', ', $missing_field_type_ids)));
      }
      else {
        $this->pass(sprintf('Field formatter %s integrates with existing field types.', $definition['id']));
      }
    }
  }

}
