<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateCckFieldValuesTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\node\Entity\Node;

/**
 * CCK field content migration.
 *
 * @group migrate_drupal
 */
class MigrateCckFieldValuesTest extends MigrateNodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'text', 'link', 'file');

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
    entity_create('field_storage_config', array(
      'entity_type' => 'node',
      'field_name' => 'field_test_three',
      'type' => 'decimal',
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'node',
      'field_name' => 'field_test_three',
      'bundle' => 'story',
    ))->save();
    entity_create('field_storage_config', array(
      'entity_type' => 'node',
      'field_name' => 'field_test_integer_selectlist',
      'type' => 'integer',
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'node',
      'field_name' => 'field_test_integer_selectlist',
      'bundle' => 'story',
    ))->save();
    entity_create('field_storage_config', array(
      'entity_type' => 'node',
      'field_name' => 'field_test_exclude_unset',
      'type' => 'text',
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'node',
      'field_name' => 'field_test_exclude_unset',
      'bundle' => 'story',
    ))->save();

    entity_create('field_storage_config', array(
      'entity_type' => 'node',
      'field_name' => 'field_multivalue',
      'type' => 'decimal',
      'precision' => '10',
      'scale' => '2',
      'cardinality' => -1,
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'node',
      'field_name' => 'field_multivalue',
      'bundle' => 'test_planet',
    ))->save();

    entity_create('field_storage_config', array(
      'entity_type' => 'node',
      'field_name' => 'field_test_identical1',
      'type' => 'integer',
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'node',
      'field_name' => 'field_test_identical1',
      'bundle' => 'story',
    ))->save();

    entity_create('field_storage_config', array(
      'entity_type' => 'node',
      'field_name' => 'field_test_identical2',
      'type' => 'integer',
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'node',
      'field_name' => 'field_test_identical2',
      'bundle' => 'story',
    ))->save();

    entity_create('field_storage_config', array(
      'entity_type' => 'node',
      'field_name' => 'field_test_link',
      'type' => 'link',
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'node',
      'field_name' => 'field_test_link',
      'bundle' => 'story',
    ))->save();

    entity_create('field_storage_config', array(
      'entity_type' => 'node',
      'field_name' => 'field_test_filefield',
      'type' => 'file',
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'node',
      'field_name' => 'field_test_filefield',
      'bundle' => 'story',
    ))->save();

    // Add some id mappings for the dependant migrations.
    $id_mappings = array(
      'd6_field_formatter_settings' => array(
        array(array('page', 'default', 'node', 'field_test'), array('node', 'page', 'default', 'field_test')),
      ),
      'd6_field_instance_widget_settings' => array(
        array(array('page', 'field_test'), array('node', 'page', 'default', 'test')),
      ),
      'd6_node' => array(
        array(array(1), array(1)),
        array(array(2), array(2)),
        array(array(3), array(3)),
      ),
    );
    $this->prepareMigrations($id_mappings);

    $migrations = entity_load_multiple('migration', array('d6_cck_field_values:*'));
    foreach ($migrations as $migration) {
      $executable = new MigrateExecutable($migration, $this);
      $executable->import();
    }

  }

  /**
   * Test CCK migration from Drupal 6 to 8.
   */
  public function testCckFields() {
    $node = Node::load(1);

    $this->assertIdentical($node->field_test->value, 'This is a shared text field');
    $this->assertIdentical($node->field_test->format, 'filtered_html');
    $this->assertIdentical($node->field_test_two->value, '10');
    $this->assertIdentical($node->field_test_two[1]->value, '20');

    $this->assertIdentical($node->field_test_three->value, '42.42', 'Single field second value is correct.');
    $this->assertIdentical($node->field_test_integer_selectlist[0]->value, '3412');
    $this->assertIdentical($node->field_test_identical1->value, '1', 'Integer value is correct');
    $this->assertIdentical($node->field_test_identical2->value, '1', 'Integer value is correct');
    $this->assertIdentical($node->field_test_exclude_unset->value, 'This is a field with exclude unset.', 'Field with exclude unset is correct.');

    // Test that link fields are migrated.
    $this->assertIdentical($node->field_test_link->uri, 'http://drupal.org/project/drupal');
    $this->assertIdentical($node->field_test_link->title, 'Drupal project page');
    $this->assertIdentical($node->field_test_link->options['attributes'], ['target' => '_blank']);

    // Test the file field meta.
    $this->assertIdentical($node->field_test_filefield->description, 'desc');
    $this->assertIdentical($node->field_test_filefield->target_id, '5');

    $planet_node = Node::load(3);
    $this->assertIdentical($planet_node->field_multivalue->value, '33.00');
    $this->assertIdentical($planet_node->field_multivalue[1]->value, '44.00');
  }

}
