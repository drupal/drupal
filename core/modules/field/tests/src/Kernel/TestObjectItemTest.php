<?php

namespace Drupal\Tests\field\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the serialization of an object.
 *
 * @group field
 */
class TestObjectItemTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_test');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a 'test_field' field and storage for validation.
    FieldStorageConfig::create(array(
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'test_object_field',
    ))->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'bundle' => 'entity_test',
    ])->save();
  }

  /**
   * Tests the serialization of a field type that has an object.
   */
  public function testTestObjectItem() {
    $object = new \stdClass();
    $object->foo = 'bar';
    $entity = EntityTest::create();
    $entity->field_test->value = $object;
    $entity->save();

    // Verify that the entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertTrue($entity->field_test->value instanceof \stdClass);
    $this->assertEquals($object, $entity->field_test->value);
  }

}
