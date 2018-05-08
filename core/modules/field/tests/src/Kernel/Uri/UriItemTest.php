<?php

namespace Drupal\Tests\field\Kernel\Uri;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests URI field functionality.
 *
 * @see \Drupal\Core\Field\Plugin\Field\FieldType\UriItem
 *
 * @group field
 */
class UriItemTest extends FieldKernelTestBase {

  /**
   * A field to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * Tests URI field.
   */
  public function testUriField() {
    $label = $this->randomMachineName();

    // Create a field with settings to validate.
    $field_name = mb_strtolower($this->randomMachineName());
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'uri',
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'label' => $label,
      'required' => TRUE,
      'settings' => [
        'size' => 123,
        'placeholder' => '',
      ],
    ]);
    $this->field->save();

    // Create a form display for the default form mode.
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, [
        'type' => 'uri',
      ])
      ->save();

    // Test the generateSampleValue() method.
    $entity = EntityTest::create();
    $entity->$field_name->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

}
