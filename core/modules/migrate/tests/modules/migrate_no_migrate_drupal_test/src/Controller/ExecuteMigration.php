<?php

namespace Drupal\migrate_no_migrate_drupal_test\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Custom controller to execute the test migrations.
 *
 * This controller class is required for the proper functional testing of
 * migration dependencies. Otherwise, the migration directly executed from the
 * functional test would use the functional test's class map and autoloader. The
 * functional test has all the classes available to it but the controller
 * does not.
 */
class ExecuteMigration extends ControllerBase {

  /**
   * Run the node_migration_no_migrate_drupal test migration.
   *
   * @return array
   *   A renderable array.
   */
  public function execute() {
    $migration_plugin_manager = \Drupal::service('plugin.manager.migration');
    $definitions = $migration_plugin_manager->getDefinitions();
    if ($definitions['node_migration_no_migrate_drupal']['label'] !== 'Node Migration No Migrate Drupal') {
      throw new InvalidPluginDefinitionException('node_migration_no_migrate_drupal');
    }
    $migrations = $migration_plugin_manager->createInstances('node_migration_no_migrate_drupal');
    $result = (new MigrateExecutable($migrations['node_migration_no_migrate_drupal']))->import();
    if ($result !== MigrationInterface::RESULT_COMPLETED) {
      throw new \RuntimeException('Migration failed');
    }

    return [
      '#type' => 'markup',
      '#markup' => 'Migration was successful.',
    ];
  }

}
