<?php

namespace Drupal\Tests\migrate\Kernel\Plugin;

use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
use Drupal\migrate\Plugin\Exception\BadPluginDefinitionException;
use Drupal\migrate_drupal\Plugin\MigrateFieldPluginManager;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

// cspell:ignore entityreference filefield imagefield nodereference
// cspell:ignore optionwidgets userreference

/**
 * Tests that modules exist for all source and destination plugins.
 *
 * @group migrate_drupal_ui
 */
class MigrationProvidersExistTest extends MigrateDrupalTestBase {

  use FileSystemModuleDiscoveryDataProviderTrait;

  /**
   * Tests that a missing source_module property raises an exception.
   */
  public function testSourceProvider() {
    $this->enableModules(['migration_provider_test']);
    $this->expectException(BadPluginDefinitionException::class);
    $this->expectExceptionMessage('The no_source_module plugin must define the source_module property.');
    $this->container->get('plugin.manager.migration')->getDefinition('migration_provider_no_annotation');
  }

  /**
   * Tests that modules exist for all source plugins.
   */
  public function testProvidersExist() {
    $this->enableAllModules();

    /** @var \Drupal\migrate\Plugin\MigrateSourcePluginManager $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.migrate.source');

    foreach ($plugin_manager->getDefinitions() as $definition) {
      $id = $definition['id'];
      $this->assertArrayHasKey('source_module', $definition, "No source_module property in '$id'");
    }
  }

  /**
   * Enable all available modules.
   */
  protected function enableAllModules() {
    // Install all available modules.
    $module_handler = $this->container->get('module_handler');
    $modules = $this->coreModuleListDataProvider();
    $modules_enabled = $module_handler->getModuleList();
    $modules_to_enable = array_keys(array_diff_key($modules, $modules_enabled));
    $this->enableModules($modules_to_enable);
  }

  /**
   * Tests that modules exist for all field plugins.
   */
  public function testFieldProvidersExist() {
    $expected_mappings = [
      'userreference' => [
        'source_module' => 'userreference',
        'destination_module' => 'core',
      ],
      'nodereference' => [
        'source_module' => 'nodereference',
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
        'destination_module' => 'image',
      ],
      'file' => [
        'source_module' => 'file',
        'destination_module' => 'file',
      ],
      'image' => [
        'source_module' => 'image',
        'destination_module' => 'image',
      ],
      'phone' => [
        'source_module' => 'phone',
        'destination_module' => 'telephone',
      ],
      'telephone' => [
        'source_module' => 'telephone',
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
      'node_reference' => [
        'source_module' => 'node_reference',
        'destination_module' => 'core',
      ],
      'user_reference' => [
        'source_module' => 'user_reference',
        'destination_module' => 'core',
      ],
    ];
    $this->enableAllModules();

    $definitions = $this->container->get('plugin.manager.migrate.field')->getDefinitions();
    foreach ($definitions as $key => $definition) {
      $this->assertArrayHasKey($key, $expected_mappings);
      $this->assertEquals($expected_mappings[$key]['source_module'], $definition['source_module']);
      $this->assertEquals($expected_mappings[$key]['destination_module'], $definition['destination_module']);
    }
  }

  /**
   * Tests a missing required definition.
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
      ->onlyMethods(['getDefinitions'])
      ->getMock();
    $discovery->method('getDefinitions')
      ->willReturn($definitions);

    $plugin_manager = $this->getMockBuilder(MigrateFieldPluginManager::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getDiscovery'])
      ->getMock();
    $plugin_manager->method('getDiscovery')
      ->willReturn($discovery);

    $this->expectException(BadPluginDefinitionException::class);
    $this->expectExceptionMessage("The missing_{$missing_property} plugin must define the $missing_property property.");
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
