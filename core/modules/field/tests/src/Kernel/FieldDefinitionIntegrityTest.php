<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Field\BaseFieldDefinition;
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
  protected static $modules = ['system', 'path_alias'];

  /**
   * Tests the integrity of field plugin definitions.
   */
  public function testFieldPluginDefinitionIntegrity(): void {
    // Enable all core modules that provide field plugins, and their
    // dependencies.
    $this->enableModules(
      $this->modulesWithSubdirectory(
        'src' . DIRECTORY_SEPARATOR . 'Plugin' . DIRECTORY_SEPARATOR . 'Field'
      )
    );

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
        $this->assertContains($definition['default_widget'], $available_field_widget_ids, sprintf('Field type %s uses a non-existent field widget by default: %s', $definition['id'], $definition['default_widget']));
      }

      // Test default field formatters.
      if (isset($definition['default_formatter'])) {
        $this->assertContains($definition['default_formatter'], $available_field_formatter_ids, sprintf('Field type %s uses a non-existent field formatter by default: %s', $definition['id'], $definition['default_formatter']));
      }
    }

    // Test the field widget plugins.
    foreach ($field_widget_manager->getDefinitions() as $definition) {
      $missing_field_type_ids = array_diff($definition['field_types'], $available_field_type_ids);
      $this->assertEmpty($missing_field_type_ids, sprintf('Field widget %s integrates with non-existent field types: %s', $definition['id'], implode(', ', $missing_field_type_ids)));
    }

    // Test the field formatter plugins.
    foreach ($field_formatter_manager->getDefinitions() as $definition) {
      $missing_field_type_ids = array_diff($definition['field_types'], $available_field_type_ids);
      $this->assertEmpty($missing_field_type_ids, sprintf('Field formatter %s integrates with non-existent field types: %s', $definition['id'], implode(', ', $missing_field_type_ids)));
    }
  }

  /**
   * Tests to load field plugin definitions used in core's existing entities.
   */
  public function testFieldPluginDefinitionAvailability(): void {
    $this->enableModules(
      $this->modulesWithSubdirectory('src' . DIRECTORY_SEPARATOR . 'Entity')
    );

    /** @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface $field_type_manager */
    $field_formatter_manager = $this->container->get('plugin.manager.field.formatter');

    /** @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface $field_type_manager */
    $field_widget_manager = $this->container->get('plugin.manager.field.widget');

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = $this->container->get('entity_field.manager');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');

    /** @var \Drupal\Core\Field\BaseFieldDefinition[][] $field_definitions */
    $field_definitions = [];

    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $content_entity_types */
    $content_entity_types = array_filter($entity_type_manager->getDefinitions(), function (EntityTypeInterface $entity_type) {
      return $entity_type instanceof ContentEntityTypeInterface;
    });

    foreach ($content_entity_types as $entity_type_id => $entity_type_definition) {
      $field_definitions[$entity_type_id] = $entity_field_manager->getBaseFieldDefinitions($entity_type_id);
    }

    foreach ($field_definitions as $entity_type_id => $definitions) {
      foreach ($definitions as $field_id => $field_definition) {
        $this->checkDisplayOption($entity_type_id, $field_id, $field_definition, $field_formatter_manager, 'view');
        $this->checkDisplayOption($entity_type_id, $field_id, $field_definition, $field_widget_manager, 'form');
      }
    }
  }

  /**
   * Helper method that tries to load plugin definitions.
   *
   * @param string $entity_type_id
   *   Id of entity type. Required by message.
   * @param string $field_id
   *   Id of field. Required by message.
   * @param \Drupal\Core\Field\BaseFieldDefinition $field_definition
   *   Field definition that provide display options.
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $plugin_manager
   *   Plugin manager that will try to provide plugin definition.
   * @param string $display_context
   *   Defines which display options should be loaded.
   */
  protected function checkDisplayOption($entity_type_id, $field_id, BaseFieldDefinition $field_definition, DiscoveryInterface $plugin_manager, $display_context): void {
    $display_options = $field_definition->getDisplayOptions($display_context);
    if (!empty($display_options['type'])) {
      $plugin = $plugin_manager->getDefinition($display_options['type'], FALSE);
      $this->assertNotNull($plugin, sprintf(
        'Plugin found for "%s" field %s display options of "%s" entity type.',
        $field_id,
        $display_context,
        $entity_type_id)
      );
    }
  }

  /**
   * Find modules with a specified subdirectory.
   *
   * @param string $subdirectory
   *   The required path, relative to the module directory.
   *
   * @return string[]
   *   A list of module names satisfying these criteria:
   *   - provided by core
   *   - not hidden
   *   - not already enabled
   *   - not in the Testing package
   *   - containing the required $subdirectory
   *   and all modules required by any of these modules.
   */
  protected function modulesWithSubdirectory($subdirectory): array {
    $modules = \Drupal::service('extension.list.module')->getList();
    $modules = array_filter($modules, function (Extension $module) use ($subdirectory) {
      // Filter contrib, hidden, already enabled modules and modules in the
      // Testing package.
      return ($module->origin === 'core'
        && empty($module->info['hidden'])
        && $module->status == FALSE
        && $module->info['package'] !== 'Testing'
        && is_readable($module->getPath() . DIRECTORY_SEPARATOR . $subdirectory));
    });
    // Gather the dependencies of the modules.
    $dependencies = NestedArray::mergeDeepArray(array_map(function (Extension $module) {
      return array_keys($module->requires);
    }, $modules));

    return array_unique(NestedArray::mergeDeep(array_keys($modules), $dependencies));
  }

}
