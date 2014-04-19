<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldEntityCountTest.
 */

namespace Drupal\field\Tests;

use Drupal\Core\Entity\ContentEntityDatabaseStorage;

/**
 * Tests entityCount() and hasData() methods on FieldConfig entity.
 *
 * @see \Drupal\field\Entity\FieldConfig::entityCount()
 * @see \Drupal\field\Entity\FieldConfig::hasData()
 */
class FieldEntityCountTest extends FieldUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Field config entityCount() and hasData() tests.',
      'description' => 'Tests entityCount() and hasData() methods on FieldConfig entity.',
      'group' => 'Field API',
    );
  }

  /**
   * Tests entityCount() and hadData() methods.
   */
  public function testEntityCountAndHasData() {
    // Create a field with a cardinality of 2 to show that we are counting
    // entities and not rows in a table.
    /** @var \Drupal\field\Entity\FieldConfig $field */
    $field = entity_create('field_config', array(
      'name' => 'field_int',
      'entity_type' => 'entity_test',
      'type' => 'integer',
      'cardinality' => 2,
    ));
    $field->save();
    entity_create('field_instance_config', array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_int',
      'bundle' => 'entity_test',
    ))->save();

    $this->assertIdentical($field->hasdata(), FALSE, 'There are no entities with field data.');
    $this->assertIdentical($field->entityCount(), 0, 'There are 0 entities with field data.');

    // Create 1 entity without the field.
    $entity = entity_create('entity_test');
    $entity->name->value = $this->randomName();
    $entity->save();

    $this->assertIdentical($field->hasdata(), FALSE, 'There are no entities with field data.');
    $this->assertIdentical($field->entityCount(), 0, 'There are 0 entities with field data.');

    // Create 12 entities to ensure that the purging works as expected.
    for ($i=0; $i < 12; $i++) {
      $entity = entity_create('entity_test');
      $value = mt_rand(1,99);
      $value2 = mt_rand(1,99);
      $entity->field_int[0]->value = $value;
      $entity->field_int[1]->value = $value2;
      $entity->name->value = $this->randomName();
      $entity->save();
    }

    $storage = \Drupal::entityManager()->getStorage('entity_test');
    if ($storage instanceof ContentEntityDatabaseStorage) {
      // Count the actual number of rows in the field table.
      $field_table_name = $storage->_fieldTableName($field);
      $result = db_select($field_table_name, 't')
        ->fields('t')
        ->countQuery()
        ->execute()
        ->fetchField();
      $this->assertEqual($result, 24, 'The field table has 24 rows.');
    }

    $this->assertIdentical($field->hasdata(), TRUE, 'There are entities with field data.');
    $this->assertEqual($field->entityCount(), 12, 'There are 12 entities with field data.');

    // Ensure the methods work on deleted fields.
    $field->delete();
    $this->assertIdentical($field->hasdata(), TRUE, 'There are entities with deleted field data.');
    $this->assertEqual($field->entityCount(), 12, 'There are 12 entities with deleted field data.');

    field_purge_batch(6);
    $this->assertIdentical($field->hasdata(), TRUE, 'There are entities with deleted field data.');
    $this->assertEqual($field->entityCount(), 6, 'There are 6 entities with deleted field data.');
  }

}
