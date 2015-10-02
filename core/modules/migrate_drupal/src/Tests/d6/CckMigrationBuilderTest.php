<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\CckMigrationBuilderTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\Core\Database\Database;

/**
 * @group migrate_drupal
 */
class CckMigrationBuilderTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $database = Database::getConnection('default', 'migrate');
    $database
      ->update('system')
      ->fields(array('status' => 0))
      ->condition('name', 'content')
      ->condition('type', 'module')
      ->execute();
    $database->schema()->dropTable('content_node_field');
    $database->schema()->dropTable('content_node_field_instance');
  }

  /**
   * Tests that the CckMigration builder performs a requirements check on the
   * source plugin.
   */
  public function testRequirementCheck() {
    $template = \Drupal::service('migrate.template_storage')
      ->getTemplateByName('d6_field');
    // Without the requirements check, this will throw a \PDOException because
    // the CCK tables do not exist.
    \Drupal::service('migrate.migration_builder')->createMigrations([$template]);
  }

}
