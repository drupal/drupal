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
  public static $modules = [
    'migrate',
    'migrate_drupal',
    // Test with a simple migration.
    'ban',
  ];

  /**
   * Test merging configuration into a plugin through the plugin manager.
   *
   * @dataProvider mergeProvider
   */
  public function testConfigurationMerge($configuration, $expected) {
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->container->get('plugin.manager.migration')->createInstance('d7_blocked_ips', $configuration);
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
    ];
  }

}
