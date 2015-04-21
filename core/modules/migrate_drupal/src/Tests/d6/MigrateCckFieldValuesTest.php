<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateCckFieldValuesTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\Core\Database\Database;
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

    $this->assertIdentical('This is a shared text field', $node->field_test->value);
    $this->assertIdentical('filtered_html', $node->field_test->format);
    $this->assertIdentical('10', $node->field_test_two->value);
    $this->assertIdentical('20', $node->field_test_two[1]->value);

    $this->assertIdentical('42.42', $node->field_test_three->value, 'Single field second value is correct.');
    $this->assertIdentical('3412', $node->field_test_integer_selectlist[0]->value);
    $this->assertIdentical('1', $node->field_test_identical1->value, 'Integer value is correct');
    $this->assertIdentical('1', $node->field_test_identical2->value, 'Integer value is correct');
    $this->assertIdentical('This is a field with exclude unset.', $node->field_test_exclude_unset->value, 'Field with exclude unset is correct.');

    // Test that link fields are migrated.
    $this->assertIdentical('http://drupal.org/project/drupal', $node->field_test_link->uri);
    $this->assertIdentical('Drupal project page', $node->field_test_link->title);
    $this->assertIdentical(['target' => '_blank'], $node->field_test_link->options['attributes']);

    // Test the file field meta.
    $this->assertIdentical('desc', $node->field_test_filefield->description);
    $this->assertIdentical('5', $node->field_test_filefield->target_id);

    $planet_node = Node::load(3);
    $value_1 = $planet_node->field_multivalue->value;
    $value_2 = $planet_node->field_multivalue[1]->value;

    // SQLite does not support scales for float data types so we need to convert
    // the value manually.
    if ($this->container->get('database')->driver() == 'sqlite') {
      $value_1 = sprintf('%01.2f', $value_1);
      $value_2 = sprintf('%01.2f', $value_2);
    }
    $this->assertIdentical('33.00', $value_1);
    $this->assertIdentical('44.00', $value_2);
  }

}
