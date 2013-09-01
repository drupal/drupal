<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\EntityReferenceItemTest.
 */

namespace Drupal\entity_reference\Tests;

use Drupal\Core\Entity\DatabaseStorageController;
use Drupal\Core\Entity\Field\FieldInterface;
use Drupal\Core\Entity\Field\FieldItemInterface;
use Drupal\Core\Language\Language;
use Drupal\field\Tests\FieldUnitTestBase;

/**
 * Tests the new entity API for the entity reference field type.
 */
class EntityReferenceItemTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_reference', 'taxonomy', 'options');

  public static function getInfo() {
    return array(
      'name' => 'Entity Reference field item',
      'description' => 'Tests using entity fields of the entity reference field type.',
      'group' => 'Entity Reference',
    );
  }

  /**
   * Sets up the test.
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('taxonomy', 'taxonomy_term_data');
    $this->installSchema('taxonomy', 'taxonomy_term_hierarchy');

    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => $this->randomName(),
      'vid' => drupal_strtolower($this->randomName()),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
    ));
    $vocabulary->save();

    $this->term = entity_create('taxonomy_term', array(
      'name' => $this->randomName(),
      'vid' => $vocabulary->id(),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
    ));
    $this->term->save();

    // Use the util to create an instance.
    entity_reference_create_instance('entity_test', 'entity_test', 'field_test_taxonomy', 'Test entity reference', 'taxonomy_term');
  }

  /**
   * Tests using entity fields of the entity reference field type.
   */
  public function testEntityReferenceItem() {
    $tid = $this->term->id();

    // Just being able to create the entity like this verifies a lot of code.
    $entity = entity_create('entity_test', array());
    $entity->field_test_taxonomy->target_id = $tid;
    $entity->name->value = $this->randomName();
    $entity->save();

    $entity = entity_load('entity_test', $entity->id());
    $this->assertTrue($entity->field_test_taxonomy instanceof FieldInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_test_taxonomy[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_test_taxonomy->target_id, $tid);
    $this->assertEqual($entity->field_test_taxonomy->entity->name->value, $this->term->name->value);
    $this->assertEqual($entity->field_test_taxonomy->entity->id(), $tid);
    $this->assertEqual($entity->field_test_taxonomy->entity->uuid(), $this->term->uuid());

    // Change the name of the term via the reference.
    $new_name = $this->randomName();
    $entity->field_test_taxonomy->entity->name = $new_name;
    $entity->field_test_taxonomy->entity->save();
    // Verify it is the correct name.
    $term = entity_load('taxonomy_term', $tid);
    $this->assertEqual($term->name->value, $new_name);

    // Make sure the computed term reflects updates to the term id.
    $term2 = entity_create('taxonomy_term', array(
      'name' => $this->randomName(),
      'vid' => $this->term->bundle(),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
    ));
    $term2->save();

    $entity->field_test_taxonomy->target_id = $term2->id();
    $this->assertEqual($entity->field_test_taxonomy->entity->id(), $term2->id());
    $this->assertEqual($entity->field_test_taxonomy->entity->name->value, $term2->name->value);

    // Delete terms so we have nothing to reference and try again
    $term->delete();
    $term2->delete();
    $entity = entity_create('entity_test', array('name' => $this->randomName()));
    $entity->save();
  }

  /**
   * Tests foreign key support.
   */
  public function testEntityReferenceFieldSchema() {
    $field = field_info_field('entity_test', 'field_test_taxonomy');
    $foreign_key_column_name = 'target_id';

    // Grab the SQL schema and verify that the 'foreign keys' are present.
    $schemas = DatabaseStorageController::_fieldSqlSchema($field);
    $schema = $schemas[DatabaseStorageController::_fieldTableName($field)];
    $this->assertEqual(count($schema['foreign keys']), 1, 'There is 1 foreign key in the schema.');

    $foreign_key = reset($schema['foreign keys']);
    $foreign_key_column = DatabaseStorageController::_fieldColumnName($field, $foreign_key_column_name);
    $this->assertEqual($foreign_key['table'], 'taxonomy_term_data', 'Foreign key table name preserved in the schema.');
    $this->assertEqual($foreign_key['columns'][$foreign_key_column], 'tid', 'Foreign key column name preserved in the schema.');

    // Create a field that references a config entity type and check that no
    // foreign key is present.
    $field_name = 'field_test_vocabulary';
    entity_reference_create_instance('entity_test', 'entity_test', $field_name, 'Test vocabulary reference', 'taxonomy_vocabulary');
    $field = field_info_field('entity_test', $field_name);

    $schemas = DatabaseStorageController::_fieldSqlSchema($field);
    $schema = $schemas[DatabaseStorageController::_fieldTableName($field)];
    $this->assertFalse(isset($schema['foreign keys']), 'There is no foreign key in the schema.');
  }
}
