<?php

namespace Drupal\Tests\field\Kernel;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the count of field data records.
 *
 * @group field
 * @see \Drupal\Core\Entity\FieldableEntityStorageInterface::countFieldData()
 * @see \Drupal\field\Entity\FieldStorageConfig::hasData()
 */
class FieldDataCountTest extends FieldKernelTestBase {

  /**
   * @var \Drupal\Core\Entity\DynamicallyFieldableEntityStorageInterface
   */
  protected $storage;

  /**
   * @var \Drupal\Core\Entity\DynamicallyFieldableEntityStorageInterface
   */
  protected $storageRev;

  /**
   * @var \Drupal\Core\Entity\DynamicallyFieldableEntityStorageInterface
   */
  protected $storageUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test_rev');
    $this->storage = \Drupal::entityTypeManager()->getStorage('entity_test');
    $this->storageRev = \Drupal::entityTypeManager()->getStorage('entity_test_rev');
    $this->storageUser = \Drupal::entityTypeManager()->getStorage('user');
  }

  /**
   * Tests entityCount() and hadData() methods.
   */
  public function testEntityCountAndHasData() {
    // Create a field with a cardinality of 2 to show that we are counting
    // entities and not rows in a table.
    /** @var \Drupal\field\Entity\FieldStorageConfig $field_storage */
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_int',
      'entity_type' => 'entity_test',
      'type' => 'integer',
      'cardinality' => 2,
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ])->save();

    $this->assertFalse($field_storage->hasData(), 'There are no entities with field data.');
    $this->assertSame(0, $this->storage->countFieldData($field_storage), 'There are 0 entities with field data.');

    // Create 1 entity without the field.
    $entity = EntityTest::create();
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    $this->assertFalse($field_storage->hasData(), 'There are no entities with field data.');
    $this->assertSame(0, $this->storage->countFieldData($field_storage), 'There are 0 entities with field data.');

    // Create 12 entities to ensure that the purging works as expected.
    for ($i = 0; $i < 12; $i++) {
      $entity = EntityTest::create();
      $entity->field_int[] = mt_rand(1, 99);
      $entity->field_int[] = mt_rand(1, 99);
      $entity->name[] = $this->randomMachineName();
      $entity->save();
    }

    $storage = \Drupal::entityTypeManager()->getStorage('entity_test');
    if ($storage instanceof SqlContentEntityStorage) {
      // Count the actual number of rows in the field table.
      $table_mapping = $storage->getTableMapping();
      $field_table_name = $table_mapping->getDedicatedDataTableName($field_storage);
      $result = Database::getConnection()->select($field_table_name, 't')
        ->fields('t')
        ->countQuery()
        ->execute()
        ->fetchField();
      $this->assertEquals(24, $result, 'The field table has 24 rows.');
    }

    $this->assertTrue($field_storage->hasData(), 'There are entities with field data.');
    $this->assertEquals(12, $this->storage->countFieldData($field_storage), 'There are 12 entities with field data.');

    // Ensure the methods work on deleted fields.
    $field_storage->delete();
    $this->assertTrue($field_storage->hasData(), 'There are entities with deleted field data.');
    $this->assertEquals(12, $this->storage->countFieldData($field_storage), 'There are 12 entities with deleted field data.');

    field_purge_batch(6);
    $this->assertTrue($field_storage->hasData(), 'There are entities with deleted field data.');
    $this->assertEquals(6, $this->storage->countFieldData($field_storage), 'There are 6 entities with deleted field data.');

    $entity_type = 'entity_test_rev';
    $this->createFieldWithStorage('_2', $entity_type);

    $entity_init = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create(['type' => $entity_type]);
    $cardinality = $this->fieldTestData->field_storage_2->getCardinality();

    $this->assertFalse($this->fieldTestData->field_storage_2->hasData(), 'There are no entities with field data.');
    $this->assertSame(0, $this->storageRev->countFieldData($this->fieldTestData->field_storage_2), 'There are 0 entities with field data.');

    // Create 1 entity with the field.
    $entity = clone($entity_init);
    $values = $this->_generateTestFieldValues($this->fieldTestData->field_storage_2->getCardinality());
    $entity->{$this->fieldTestData->field_name_2} = $values;
    $entity->setNewRevision();
    $entity->save();
    $first_revision = $entity->getRevisionId();

    $this->assertTrue($this->fieldTestData->field_storage_2->hasData(), 'There are entities with field data.');
    $this->assertSame(1, $this->storageRev->countFieldData($this->fieldTestData->field_storage_2), 'There is 1 entity with field data.');

    $entity->{$this->fieldTestData->field_name_2} = [];
    $entity->setNewRevision();
    $entity->save();

    $this->assertTrue($this->fieldTestData->field_storage_2->hasData(), 'There are entities with field data.');

    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage($entity_type);
    $entity = $storage->loadRevision($first_revision);
    $this->assertCount($cardinality, $entity->{$this->fieldTestData->field_name_2}, "Revision $first_revision: expected number of values.");
  }

  /**
   * Verify that we can count a table that contains an entry with index 0.
   */
  public function testCountWithIndex0() {
    // Create a field that will require dedicated storage.
    /** @var \Drupal\field\Entity\FieldStorageConfig $field_storage */
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_int',
      'entity_type' => 'user',
      'type' => 'integer',
      'cardinality' => 2,
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'user',
    ])->save();

    // Create an entry for the anonymous user, who has user ID 0.
    $user = $this->storageUser
      ->create([
        'uid' => 0,
        'name' => 'anonymous',
        'mail' => NULL,
        'status' => FALSE,
        'field_int' => 42,
      ]);
    $user->save();

    // Test shared table storage.
    $storage = $user->getFieldDefinition('name')->getFieldStorageDefinition();
    $this->assertTrue($this->storageUser->countFieldData($storage, TRUE));

    // Test dedicated table storage.
    $storage = $user->getFieldDefinition('field_int')->getFieldStorageDefinition();
    $this->assertTrue($this->storageUser->countFieldData($storage, TRUE));
  }

}
