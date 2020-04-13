<?php

namespace Drupal\Tests\migrate\Kernel\Plugin;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the migration plugin manager.
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\MigratePluginManager
 * @group migrate
 */
class MigrationPluginConfigurationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate',
    'migrate_drupal',
    // Test with a simple migration.
    'ban',
    'locale',
  ];

  /**
   * Test merging configuration into a plugin through the plugin manager.
   *
   * @dataProvider mergeProvider
   */
  public function testConfigurationMerge($id, $configuration, $expected) {
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->container->get('plugin.manager.migration')
      ->createInstance($id, $configuration);
    $source_configuration = $migration->getSourceConfiguration();
    $this->assertEquals($expected, $source_configuration);
  }

  /**
   * Provide configuration data for testing.
   */
  public function mergeProvider() {
    return [
      // Tests adding new configuration to a migration.
      [
        // New configuration.
        'd7_blocked_ips',
        [
          'source' => [
            'constants' => [
              'added_setting' => 'Ban them all!',
            ],
          ],
        ],
        // Expected final source configuration.
        [
          'plugin' => 'd7_blocked_ips',
          'constants' => [
            'added_setting' => 'Ban them all!',
          ],
        ],
      ],
      // Tests overriding pre-existing configuration in a migration.
      [
        // New configuration.
        'd7_blocked_ips',
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
