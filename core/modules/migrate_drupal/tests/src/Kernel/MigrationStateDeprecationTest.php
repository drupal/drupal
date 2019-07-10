<?php

namespace Drupal\Tests\migrate_drupal\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Defines a class for testing deprecation error from MigrationState.
 *
 * @group migrate_drupal
 * @group legacy
 */
class MigrationStateDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate_drupal',
    'migrate',
    'migrate_state_no_file_test',
  ];

  /**
   * Tests migration state deprecation notice.
   *
   * Test that a module with a migration but without a .migrate_drupal.yml
   * trigger deprecation errors.
   *
   * @doesNotPerformAssertions
   * @expectedDeprecation Using migration plugin definitions to determine the migration state of the module 'migrate_state_no_file_test' is deprecated in Drupal 8.7. Add the module to a migrate_drupal.yml file. See https://www.drupal.org/node/2929443
   */
  public function testUndeclaredDestinationDeprecation() {
    $plugin_manager = \Drupal::service('plugin.manager.migration');
    $all_migrations = $plugin_manager->createInstancesByTag('Drupal 7');

    \Drupal::service('migrate_drupal.migration_state')
      ->getUpgradeStates(7, [
        'module' => [
          'migrate_state_no_file_test' => [
            'name' => 'migrate_state_no_file_test',
            'status' => TRUE,
          ],
        ],
      ], ['import' => $all_migrations['migrate_state_no_file_test']]);
  }

}
