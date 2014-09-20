<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldDataCountTest.
 */

namespace Drupal\field\Tests;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Tests counting field data records and the hasData() method on
 * FieldStorageConfig entity.
 *
 * @group field
 * @see \Drupal\Core\Entity\FieldableEntityStorageInterface::countFieldData()
 * @see \Drupal\field\Entity\FieldStorageConfig::hasData()
 */
class FieldDataCountTest extends FieldUnitTestBase {

  /**
   * @var \Drupal\Core\Entity\FieldableEntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->storage = \Drupal::entityManager()->getStorage('entity_test');
  }

  /**
   * Tests entityCount() and hadData() methods.
   */
  public function testEntityCountAndHasData() {
    // Create a field with a cardinality of 2 to show that we are counting
    // entities and not rows in a table.
    /** @var \Drupal\field\Entity\FieldStorageConfig $field_storage */
    $field_storage = entity_create('field_storage_config', array(
      'name' => 'field_int',
      'entity_type' => 'entity_test',
      'type' => 'integer',
      'cardinality' => 2,
    ));
    $field_storage->save();
    entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ))->save();

    $this->assertIdentical($field_storage->hasdata(), FALSE, 'There are no entities with field data.');
    $this->assertIdentical($this->storage->countFieldData($field_storage), 0, 'There are 0 entities with field data.');

    // Create 1 entity without the field.
    $entity = entity_create('entity_test');
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    $this->assertIdentical($field_storage->hasdata(), FALSE, 'There are no entities with field data.');
    $this->assertIdentical($this->storage->countFieldData($field_storage), 0, 'There are 0 entities with field data.');

    // Create 12 entities to ensure that the purging works as expected.
    for ($i=0; $i < 12; $i++) {
      $entity = entity_create('entity_test');
      $value = mt_rand(1,99);
      $value2 = mt_rand(1,99);
      $entity->field_int[0]->value = $value;
      $entity->field_int[1]->value = $value2;
      $entity->name->value = $this->randomMachineName();
      $entity->save();
    }

    $storage = \Drupal::entityManager()->getStorage('entity_test');
    if ($storage instanceof SqlContentEntityStorage) {
      // Count the actual number of rows in the field table.
      $table_mapping = $storage->getTableMapping();
      $field_table_name = $table_mapping->getDedicatedDataTableName($field_storage);
      $result = db_select($field_table_name, 't')
        ->fields('t')
        ->countQuery()
        ->execute()
        ->fetchField();
      $this->assertEqual($result, 24, 'The field table has 24 rows.');
    }

    $this->assertIdentical($field_storage->hasdata(), TRUE, 'There are entities with field data.');
    $this->assertEqual($this->storage->countFieldData($field_storage), 12, 'There are 12 entities with field data.');

    // Ensure the methods work on deleted fields.
    $field_storage->delete();
    $this->assertIdentical($field_storage->hasdata(), TRUE, 'There are entities with deleted field data.');
    $this->assertEqual($this->storage->countFieldData($field_storage), 12, 'There are 12 entities with deleted field data.');

    field_purge_batch(6);
    $this->assertIdentical($field_storage->hasdata(), TRUE, 'There are entities with deleted field data.');
    $this->assertEqual($this->storage->countFieldData($field_storage), 6, 'There are 6 entities with deleted field data.');
  }

}
