<?php

namespace Drupal\Tests\migrate_drupal\Unit;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\migrate_drupal\Annotation\MigrateField;
use Drupal\migrate_drupal\Plugin\MigrateFieldPluginManager;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the MigrateFieldPluginManager class.
 *
 * @group migrate_drupal
 * @coversDefaultClass \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManager
 */
class MigrateFieldPluginManagerTest extends UnitTestCase {

  /**
   * Tests the plugin weighting system.
   *
   * @covers ::getPluginIdFromFieldType
   * @covers ::sortDefinitions
   * @covers ::findDefinitions
   * @dataProvider weightsData
   */
  public function testWeights($field_type, $core, $expected_plugin_id) {
    /** @var \Drupal\Core\Cache\CacheBackendInterface $cache */
    $cache = $this->prophesize(CacheBackendInterface::class)->reveal();
    /** @var \Drupal\Core\Extension\ModuleHandlerInterfaceModuleHandlerInterface $module_handler */
    $module_handler = $this->prophesize(ModuleHandlerInterface::class)->reveal();
    $discovery = $this->prophesize(AnnotatedClassDiscovery::class);
    $discovery->getDefinitions()->willReturn($this->pluginFixtureData());
    $manager = new MigrateFieldPluginManagerTestClass('field', new \ArrayObject(), $cache, $module_handler, MigrateField::class, $discovery->reveal());
    if (!$expected_plugin_id) {
      $this->expectException(PluginNotFoundException::class);
      $this->expectExceptionMessage(sprintf("Plugin ID '%s' was not found.", $field_type));
    }
    $actual_plugin_id = $manager->getPluginIdFromFieldType($field_type, ['core' => $core]);
    $this->assertSame($expected_plugin_id, $actual_plugin_id);

  }

  /**
   * Provides data for testWeights().
   *
   * @return array
   *   The data.
   */
  public function weightsData() {
    return [
      'Field 1, D6' => [
        'field_type' => 'field_1',
        'core' => 6,
        'expected_plugin_id' => 'core_replacement_plugin',
      ],
      'Field 2, D6' => [
        'field_type' => 'field_2',
        'core' => 6,
        'expected_plugin_id' => 'field_1',
      ],
      'Field 3, D6' => [
        'field_type' => 'field_3',
        'core' => 6,
        'expected_plugin_id' => 'field_3',
      ],
      'Field 4, D6' => [
        'field_type' => 'field_4',
        'core' => 6,
        'expected_plugin_id' => 'field_4',
      ],
      'Field 5, D6' => [
        'field_type' => 'field_5',
        'core' => 6,
        'expected_plugin_id' => 'alphabetically_second',
      ],
      'Field 1, D7' => [
        'field_type' => 'field_1',
        'core' => 7,
        'expected_plugin_id' => 'core_replacement_plugin',
      ],
      'Field 2, D7' => [
        'field_type' => 'field_2',
        'core' => 7,
        'expected_plugin_id' => FALSE,
      ],
      'Field 3, D7' => [
        'field_type' => 'field_3',
        'core' => 7,
        'expected_plugin_id' => 'field_3',
      ],
      'Field 4, D7' => [
        'field_type' => 'field_4',
        'core' => 7,
        'expected_plugin_id' => 'contrib_override_plugin',
      ],
      'Field 5, D7' => [
        'field_type' => 'field_5',
        'core' => 7,
        'expected_plugin_id' => 'alphabetically_first',
      ],
    ];
  }

  /**
   * Returns test plugin data for the test class to use.
   *
   * @return array
   *   The test plugin data.
   */
  protected function pluginFixtureData() {
    return [
      // Represents a deprecated core field plugin that applied to field_1
      // and field_2 for Drupal 6.
      'field_1' => [
        'weight' => 99999999,
        'core' => [6],
        'type_map' => [
          'field_1' => 'field_1',
          'field_2' => 'field_2',
        ],
        'source_module' => 'system',
        'destination_module' => 'system',
      ],
      // Replacement for deprecated plugin for field_1 in Drupal 6 and 7.
      // Does not provide replacement for field_2.
      'core_replacement_plugin' => [
        'weight' => 0,
        'core' => [6, 7],
        'type_map' => [
          'field_1' => 'field_1',
        ],
        'source_module' => 'system',
        'destination_module' => 'system',
      ],
      // Represents a core plugin with no type_map, applies to field_3 due to
      // plugin id.
      'field_3' => [
        'core' => [6, 7],
        'type_map' => [],
        'weight' => 0,
        'source_module' => 'system',
        'destination_module' => 'system',
      ],
      // Represents a core plugin with no type_map, applies to field_4 due to
      // plugin id.
      'field_4' => [
        'core' => [6, 7],
        'type_map' => [],
        'weight' => 0,
        'source_module' => 'system',
        'destination_module' => 'system',
      ],
      // Represents a contrib plugin overriding field_4 for Drupal 7 only.
      'contrib_override_plugin' => [
        'weight' => -100,
        'core' => [7],
        'type_map' => [
          'field_4' => 'field_4',
        ],
        'source_module' => 'system',
        'destination_module' => 'system',
      ],
      // field_5 is served by alphabetically_second in Drupal 6 and
      // alphabetically_first and alphabetically_second in Drupal 7.  It should
      // be served by the alphabetically_first in Drupal 7 regardless of the
      // order they appear here.
      'alphabetically_second' => [
        'weight' => 0,
        'core' => [6, 7],
        'type_map' => [
          'field_5' => 'field_5',
        ],
        'source_module' => 'system',
        'destination_module' => 'system',
      ],
      'alphabetically_first' => [
        'weight' => 0,
        'core' => [7],
        'type_map' => [
          'field_5' => 'field_5',
        ],
        'source_module' => 'system',
        'destination_module' => 'system',
      ],
    ];
  }

}

/**
 * Class to test MigrateFieldPluginManager.
 *
 * Overrides the constructor to inject a mock discovery class to provide a test
 * list of plugins.
 */
class MigrateFieldPluginManagerTestClass extends MigrateFieldPluginManager {

  /**
   * Constructs a MigratePluginManagerTestClass object.
   *
   * @param string $type
   *   The type of the plugin: row, source, process, destination, entity_field,
   *   id_map.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param string $annotation
   *   The annotation class name.
   * @param \Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery $discovery
   *   A mock plugin discovery object for the test class to use.
   */
  public function __construct($type, \Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, $annotation, AnnotatedClassDiscovery $discovery) {
    parent::__construct($type, $namespaces, $cache_backend, $module_handler, $annotation);
    $this->discovery = $discovery;
  }

}
