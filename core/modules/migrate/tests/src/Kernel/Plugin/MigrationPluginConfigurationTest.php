<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel\Plugin;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\MigratePluginManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the migration plugin manager.
 */
#[CoversClass(MigratePluginManager::class)]
#[Group('migrate')]
#[RunTestsInSeparateProcesses]
class MigrationPluginConfigurationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate',
    'migrate_drupal',
    // Test with a simple migration.
    'migrate_plugin_config_test',
    'locale',
  ];

  /**
   * Tests merging configuration into a plugin through the plugin manager.
   */
  #[DataProvider('mergeProvider')]
  public function testConfigurationMerge($id, $configuration, $expected): void {
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->container->get('plugin.manager.migration')
      ->createInstance($id, $configuration);
    $source_configuration = $migration->getSourceConfiguration();
    $this->assertEquals($expected, $source_configuration);
  }

  /**
   * Provide configuration data for testing.
   */
  public static function mergeProvider() {
    return [
      // Tests adding new configuration to a migration.
      [
        // New configuration.
        'simple_migration',
        [
          'source' => [
            'constants' => [
              'added_setting' => 'Ban them all!',
            ],
          ],
        ],
        // Expected final source configuration.
        [
          'plugin' => 'simple_source',
          'constants' => [
            'added_setting' => 'Ban them all!',
          ],
        ],
      ],
      // Tests overriding pre-existing configuration in a migration.
      [
        // New configuration.
        'simple_migration',
        [
          'source' => [
            'plugin' => 'a_different_plugin',
          ],
        ],
        // Expected final source configuration.
        [
          'plugin' => 'a_different_plugin',
        ],
      ],
      // New configuration.
      [
        'locale_settings',
        [
          'source' => [
            'plugin' => 'variable',
            'variables' => [
              'locale_cache_strings',
              'locale_js_directory',
            ],
            'source_module' => 'locale',
          ],
        ],
        // Expected final source and process configuration.
        [
          'plugin' => 'variable',
          'variables' => [
            'locale_cache_strings',
            'locale_js_directory',
          ],
          'source_module' => 'locale',
        ],
      ],
    ];
  }

}
