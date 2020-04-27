<?php

namespace Drupal\Tests\telephone\Kernel;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the new entity API for the telephone field type.
 *
 * @group telephone
 */
class TelephoneItemTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['telephone'];

  protected function setUp() {
    parent::setUp();

    // Create a telephone field storage and field for validation.
    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'telephone',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'bundle' => 'entity_test',
      'default_value' => [0 => ['value' => '+012345678']],
    ])->save();
  }

  /**
   * Tests using entity fields of the telephone field type.
   */
  public function testTestItem() {
    // Verify entity creation.
    $entity = EntityTest::create();
    $value = '+0123456789';
    $entity->field_test = $value;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->field_test);
    $this->assertInstanceOf(FieldItemInterface::class, $entity->field_test[0]);
    $this->assertEqual($entity->field_test->value, $value);
    $this->assertEqual($entity->field_test[0]->value, $value);

    // Verify changing the field value.
    $new_value = '+41' . rand(1000000, 9999999);
    $entity->field_test->value = $new_value;
    $this->assertEqual($entity->field_test->value, $new_value);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->field_test->value, $new_value);

    // Test sample item generation.
    $entity = EntityTest::create();
    $entity->field_test->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

}
