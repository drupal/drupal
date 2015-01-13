<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateCckFieldRevisionTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;

/**
 * CCK field revision migration.
 *
 * @group migrate_drupal
 */
class MigrateCckFieldRevisionTest extends MigrateNodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field', 'filter', 'node', 'text');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    entity_create('field_storage_config', array(
      'entity_type' => 'node',
      'field_name' => 'field_test',
      'type' => 'text',
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'node',
      'field_name' => 'field_test',
      'bundle' => 'story',
    ))->save();
    entity_create('field_storage_config', array(
      'entity_type' => 'node',
      'field_name' => 'field_test_two',
      'type' => 'integer',
      'cardinality' => -1,
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'node',
      'field_name' => 'field_test_two',
      'bundle' => 'story',
    ))->save();

    // Add some id mappings for the dependant migrations.
    $id_mappings = array(
      'd6_cck_field_values' => array(
        array(array(1), array(1)),
      ),
      'd6_node' => array(
        array(array(1), array(1)),
        array(array(2), array(2)),
      ),
      'd6_node_revision' => array(
        array(array(1), array(1)),
      ),
    );
    $this->prepareMigrations($id_mappings);
    $migrations = entity_load_multiple('migration', array('d6_cck_field_revision:*'));
    foreach ($migrations as $migration) {
      $executable = new MigrateExecutable($migration, $this);
      $executable->import();
    }
  }

  /**
   * Test CCK revision migration from Drupal 6 to 8.
   */
  public function testCckFieldRevision() {
    $node = \Drupal::entityManager()->getStorage('node')->loadRevision(2);
    $this->assertEqual($node->id(), 1, 'Node 1 loaded.');
    $this->assertEqual($node->getRevisionId(), 2, 'Node 1 revision 2loaded.');
  }

}
