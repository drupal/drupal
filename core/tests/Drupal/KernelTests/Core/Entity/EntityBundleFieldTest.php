<?php

namespace Drupal\KernelTests\Core\Entity;

/**
 * Tests adding a custom bundle field.
 *
 * @group Entity
 */
class EntityBundleFieldTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_schema_test');

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The database connection used.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('user', array('users_data'));
    $this->moduleHandler = $this->container->get('module_handler');
    $this->database = $this->container->get('database');
  }

  /**
   * Tests making use of a custom bundle field.
   */
  public function testCustomBundleFieldUsage() {
    entity_test_create_bundle('custom');

    // Check that an entity with bundle entity_test does not have the custom
    // field.
    $storage = $this->entityManager->getStorage('entity_test');
    $entity = $storage->create([
      'type' => 'entity_test',
    ]);
    $this->assertFalse($entity->hasField('custom_bundle_field'));

    // Check that the custom bundle has the defined custom field and check
    // saving and deleting of custom field data.
    $entity = $storage->create([
      'type' => 'custom',
    ]);
    $this->assertTrue($entity->hasField('custom_bundle_field'));

    // Ensure that the field exists in the field map.
    $field_map = \Drupal::entityManager()->getFieldMap();
    $this->assertEqual($field_map['entity_test']['custom_bundle_field'], ['type' => 'string', 'bundles' => ['custom' => 'custom']]);

    $entity->custom_bundle_field->value = 'swanky';
    $entity->save();
    $storage->resetCache();
    $entity = $storage->load($entity->id());
    $this->assertEqual($entity->custom_bundle_field->value, 'swanky', 'Entity was saved correctly');

    $entity->custom_bundle_field->value = 'cozy';
    $entity->save();
    $storage->resetCache();
    $entity = $storage->load($entity->id());
    $this->assertEqual($entity->custom_bundle_field->value, 'cozy', 'Entity was updated correctly.');

    $entity->delete();
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $storage->getTableMapping();
    $table = $table_mapping->getDedicatedDataTableName($entity->getFieldDefinition('custom_bundle_field'));
    $result = $this->database->select($table, 'f')
      ->fields('f')
      ->condition('f.entity_id', $entity->id())
      ->execute();
    $this->assertFalse($result->fetchAssoc(), 'Field data has been deleted');

    // Create another entity to test that values are marked as deleted when a
    // bundle is deleted.
    $entity = $storage->create(['type' => 'custom', 'custom_bundle_field' => 'new']);
    $entity->save();
    entity_test_delete_bundle('custom');

    $table = $table_mapping->getDedicatedDataTableName($entity->getFieldDefinition('custom_bundle_field'));
    $result = $this->database->select($table, 'f')
      ->condition('f.entity_id', $entity->id())
      ->condition('deleted', 1)
      ->countQuery()
      ->execute();
    $this->assertEqual(1, $result->fetchField(), 'Field data has been deleted');

    // Ensure that the field no longer exists in the field map.
    $field_map = \Drupal::entityManager()->getFieldMap();
    $this->assertFalse(isset($field_map['entity_test']['custom_bundle_field']));

    // @todo Test field purge and table deletion once supported. See
    //   https://www.drupal.org/node/2282119.
    // $this->assertFalse($this->database->schema()->tableExists($table), 'Custom field table was deleted');
  }

}
