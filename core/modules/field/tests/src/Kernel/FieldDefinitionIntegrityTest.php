<?php

namespace Drupal\Tests\field\Kernel;

use Drupal\Core\Extension\Extension;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the integrity of field API plugin definitions.
 *
 * @group field
 */
class FieldDefinitionIntegrityTest extends KernelTestBase {

  /**
   * @var array
   */
  public static $modules = ['system'];

  /**
   * Tests the integrity of field plugin definitions.
   */
  public function testFieldPluginDefinitionIntegrity() {

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

    /** @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface $field_type_manager */
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');

    /** @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface $field_type_manager */
    $field_formatter_manager = \Drupal::service('plugin.manager.field.formatter');

    /** @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface $field_type_manager */
    $field_widget_manager = \Drupal::service('plugin.manager.field.widget');

    // Load the IDs of all available field type plugins.
    $available_field_type_ids = [];
    foreach ($field_type_manager->getDefinitions() as $definition) {
      $available_field_type_ids[] = $definition['id'];
    }

    // Load the IDs of all available field widget plugins.
    $available_field_widget_ids = [];
    foreach ($field_widget_manager->getDefinitions() as $definition) {
      $available_field_widget_ids[] = $definition['id'];
    }

    // Load the IDs of all available field formatter plugins.
    $available_field_formatter_ids = [];
    foreach ($field_formatter_manager->getDefinitions() as $definition) {
      $available_field_formatter_ids[] = $definition['id'];
    }

    // Test the field type plugins.
    foreach ($field_type_manager->getDefinitions() as $definition) {
      // Test default field widgets.
      if (isset($definition['default_widget'])) {
        if (in_array($definition['default_widget'], $available_field_widget_ids)) {
          $this->pass(sprintf('Field type %s uses an existing field widget by default.', $definition['id']));
        }
        else {
          $this->fail(sprintf('Field type %s uses a non-existent field widget by default: %s', $definition['id'], $definition['default_widget']));
        }
      }

      // Test default field formatters.
      if (isset($definition['default_formatter'])) {
        if (in_array($definition['default_formatter'], $available_field_formatter_ids)) {
          $this->pass(sprintf('Field type %s uses an existing field formatter by default.', $definition['id']));
        }
        else {
          $this->fail(sprintf('Field type %s uses a non-existent field formatter by default: %s', $definition['id'], $definition['default_formatter']));
        }
      }
    }

    // Test the field widget plugins.
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
