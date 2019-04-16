<?php

namespace Drupal\Tests\migrate\Kernel\Plugin;

use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Tests that migrations exist in the migration_templates directory.
 *
 * @group migrate
 * @group legacy
 */
class MigrationDirectoryTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migration_directory_test'];

  /**
   * Tests that migrations in the migration_templates directory are created.
   *
   * @expectedDeprecation Use of the /migration_templates directory to store migration configuration files is deprecated in Drupal 8.1.0 and will be removed before Drupal 9.0.0. See https://www.drupal.org/node/2920988.
   */
  public function testMigrationDirectory() {
    /** @var \Drupal\migrate\Plugin\MigrationPluginManager $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.migration');
    // Tests that a migration in directory 'migration_templates' is discovered.
    $this->assertTrue($plugin_manager->hasDefinition('migration_templates_test'));
  }

}
