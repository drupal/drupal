<?php

namespace Drupal\Tests\migrate\Kernel\Plugin;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
use Drupal\migrate\Plugin\Exception\BadPluginDefinitionException;
use Drupal\migrate_drupal\Plugin\MigrateFieldPluginManager;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Tests that modules exist for all source and destination plugins.
 *
 * @group migrate_drupal_ui
 */
class MigrationProvidersExistTest extends MigrateDrupalTestBase {

  use FileSystemModuleDiscoveryDataProviderTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migration_provider_test'];

  /**
   * Tests that modules exist for all source and destination plugins.
   */
  public function testProvidersExist() {
    // Install all available modules.
    $module_handler = $this->container->get('module_handler');
    $modules = $this->coreModuleListDataProvider();
    $modules_enabled = $module_handler->getModuleList();
    $modules_to_enable = array_keys(array_diff_key($modules, $modules_enabled));
    $this->enableModules($modules_to_enable);

    /** @var \Drupal\migrate\Plugin\MigrationPluginManager $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.migration');
    // Get all the migrations.
    $migrations = $plugin_manager->createInstances(array_keys($plugin_manager->getDefinitions()));
    // Ensure the test module was enabled.
    $this->assertTrue(array_key_exists('migration_provider_test', $migrations));
    $this->assertTrue(array_key_exists('migration_provider_no_annotation', $migrations));
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    foreach ($migrations as $migration) {
      $source_module = $migration->getSourcePlugin()->getSourceModule();
      $destination_module = $migration->getDestinationPlugin()->getDestinationModule();
      $migration_id = $migration->getPluginId();
      if ($migration_id == 'migration_provider_test') {
        $this->assertFalse($source_module, new FormattableMarkup('Source module not found for @migration_id.', ['@migration_id' => $migration_id]));
        $this->assertFalse($destination_module, new FormattableMarkup('Destination module not found for @migration_id.', ['@migration_id' => $migration_id]));
      }
      elseif ($migration_id == 'migration_provider_no_annotation') {
        $this->assertFalse($source_module, new FormattableMarkup('Source module not found for @migration_id.', ['@migration_id' => $migration_id]));
        $this->assertTrue($destination_module, new FormattableMarkup('Destination module found for @migration_id.', ['@migration_id' => $migration_id]));
      }
      else {
        $this->assertTrue($source_module, new FormattableMarkup('Source module found for @migration_id.', ['@migration_id' => $migration_id]));
        $this->assertTrue($destination_module, new FormattableMarkup('Destination module found for @migration_id.', ['@migration_id' => $migration_id]));
      }
      // Destination module can't be migrate or migrate_drupal or
      // migrate_drupal_ui.
      $invalid_destinations = ['migrate', 'migrate_drupal', 'migrate_drupal_ui'];
      $this->assertNotContains($destination_module, $invalid_destinations, new FormattableMarkup('Invalid destination for @migration_id.', ['@migration_id' => $migration_id]));
    }
  }

  /**
   * Tests that modules exist for all field plugins.
   */
  public function testFieldProvidersExist() {
    $expected_mappings = [
      'userreference' => [
        'source_module' => 'user_reference',
        'destination_module' => 'core',
      ],
      'nodereference' => [
        'source_module' => 'node_reference',
        'destination_module' => 'core',
      ],
      'optionwidgets' => [
        'source_module' => 'optionwidgets',
        'destination_module' => 'options',
      ],
      'list' => [
        'source_module' => 'list',
        'destination_module' => 'options',
      ],
      'options' => [
        'source_module' => 'options',
        'destination_module' => 'options',
      ],
      'filefield' => [
        'source_module' => 'filefield',
        'destination_module' => 'file',
      ],
      'imagefield' => [
        'source_module' => 'imagefield',
        'destination_module' => 'file',
      ],
      'file' => [
        'source_module' => 'file',
        'destination_module' => 'file',
      ],
      'image' => [
        'source_module' => 'image',
        'destination_module' => 'file',
      ],
      'phone' => [
        'source_module' => 'phone',
        'destination_module' => 'telephone',
      ],
      'link' => [
        'source_module' => 'link',
        'destination_module' => 'link',
      ],
      'link_field' => [
        'source_module' => 'link',
        'destination_module' => 'link',
      ],
      'd6_text' => [
        'source_module' => 'text',
        'destination_module' => 'text',
      ],
      'd7_text' => [
        'source_module' => 'text',
        'destination_module' => 'text',
      ],
      'taxonomy_term_reference' => [
        'source_module' => 'taxonomy',
        'destination_module' => 'core',
      ],
      'date' => [
        'source_module' => 'date',
        'destination_module' => 'datetime',
      ],
      'datetime' => [
        'source_module' => 'date',
        'destination_module' => 'datetime',
      ],
      'email' => [
        'source_module' => 'email',
        'destination_module' => 'core',
      ],
      'number_default' => [
        'source_module' => 'number',
        'destination_module' => 'core',
      ],
      'entityreference' => [
        'source_module' => 'entityreference',
        'destination_module' => 'core',
      ],
    ];
    // Install all available modules.
    $module_handler = $this->container->get('module_handler');
    $modules = $this->coreModuleListDataProvider();
    $modules_enabled = $module_handler->getModuleList();
    $modules_to_enable = array_keys(array_diff_key($modules, $modules_enabled));
    $this->enableModules($modules_to_enable);

    /** @var \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManager $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.migrate.field');
    $definitions = $plugin_manager->getDefinitions();
    foreach ($definitions as $key => $definition) {
      $this->assertArrayHasKey($key, $expected_mappings);
      $this->assertEquals($expected_mappings[$key]['source_module'], $definition['source_module']);
      $this->assertEquals($expected_mappings[$key]['destination_module'], $definition['destination_module']);
    }
  }

  /**
   * Test a missing required definition.
   *
   * @param array $definitions
   *   A field plugin definition.
   * @param string $missing_property
   *   The name of the property missing from the definition.
   *
   * @dataProvider fieldPluginDefinitionsProvider
   */
  public function testFieldProviderMissingRequiredProperty(array $definitions, $missing_property) {
    $discovery = $this->getMockBuilder(MigrateFieldPluginManager::class)
      ->disableOriginalConstructor()
      ->setMethods(['getDefinitions'])
      ->getMock();
    $discovery->method('getDefinitions')
      ->willReturn($definitions);

    $plugin_manager = $this->getMockBuilder(MigrateFieldPluginManager::class)
      ->disableOriginalConstructor()
      ->setMethods(['getDiscovery'])
      ->getMock();
    $plugin_manager->method('getDiscovery')
      ->willReturn($discovery);

    $this->setExpectedException(BadPluginDefinitionException::class, "The missing_{$missing_property} plugin should define the $missing_property property.");
    $plugin_manager->getDefinitions();
  }

  /**
   * Data provider for field plugin definitions.
   *
   * @return array
   *   Array of plugin definitions.
   */
  public function fieldPluginDefinitionsProvider() {
    return [
      'missing_core_scenario' => [
        'definitions' => [
          'missing_core' => [
            'source_module' => 'migrate',
            'destination_module' => 'migrate',
            'id' => 'missing_core',
            'class' => 'foo',
            'provider' => 'foo',
          ],
        ],
        'missing_property' => 'core',
      ],
      'missing_source_scenario' => [
        'definitions' => [
          'missing_source_module' => [
            'core' => [6, 7],
            'destination_module' => 'migrate',
            'id' => 'missing_source_module',
            'class' => 'foo',
            'provider' => 'foo',
          ],
        ],
        'missing_property' => 'source_module',
      ],
      'missing_destination_scenario' => [
        'definitions' => [
          'missing_destination_module' => [
            'core' => [6, 7],
            'source_module' => 'migrate',
            'id' => 'missing_destination_module',
            'class' => 'foo',
            'provider' => 'foo',
          ],
        ],
        'missing_property' => 'destination_module',
      ],
    ];
  }

}
