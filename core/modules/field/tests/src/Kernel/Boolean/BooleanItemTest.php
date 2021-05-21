<?php

namespace Drupal\Tests\field\Kernel\Boolean;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the new entity API for the boolean field type.
 *
 * @group field
 */
class BooleanItemTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a boolean field and storage for validation.
    FieldStorageConfig::create([
      'field_name' => 'field_boolean',
      'entity_type' => 'entity_test',
      'type' => 'boolean',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_boolean',
      'bundle' => 'entity_test',
    ])->save();

    // Create a form display for the default form mode.
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('entity_test', 'entity_test')
      ->setComponent('field_boolean', [
        'type' => 'boolean_checkbox',
      ])
      ->save();
  }

  /**
   * Tests using entity fields of the boolean field type.
   */
  public function testBooleanItem() {
    // Verify entity creation.
    $entity = EntityTest::create();
    $value = '1';
    $entity->field_boolean = $value;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->field_boolean);
    $this->assertInstanceOf(FieldItemInterface::class, $entity->field_boolean[0]);
    $this->assertEquals($value, $entity->field_boolean->value);
    $this->assertEquals($value, $entity->field_boolean[0]->value);

    // Verify changing the boolean value.
    $new_value = 0;
    $entity->field_boolean->value = $new_value;
    $this->assertEquals($new_value, $entity->field_boolean->value);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = EntityTest::load($id);
    $this->assertEquals($new_value, $entity->field_boolean->value);

    // Test sample item generation.
    $entity = EntityTest::create();
    $entity->field_boolean->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

}
