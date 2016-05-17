<?php

namespace Drupal\Tests\field\Kernel\Email;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the new entity API for the email field type.
 *
 * @group field
 */
class EmailItemTest extends FieldKernelTestBase {

  protected function setUp() {
    parent::setUp();

    // Create an email field storage and field for validation.
    FieldStorageConfig::create(array(
      'field_name' => 'field_email',
      'entity_type' => 'entity_test',
      'type' => 'email',
    ))->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_email',
      'bundle' => 'entity_test',
    ])->save();

    // Create a form display for the default form mode.
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent('field_email', array(
        'type' => 'email_default',
      ))
      ->save();
  }

  /**
   * Tests using entity fields of the email field type.
   */
  public function testEmailItem() {
    // Verify entity creation.
    $entity = EntityTest::create();
    $value = 'test@example.com';
    $entity->field_email = $value;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertTrue($entity->field_email instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_email[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_email->value, $value);
    $this->assertEqual($entity->field_email[0]->value, $value);

    // Verify changing the email value.
    $new_value = $this->randomMachineName();
    $entity->field_email->value = $new_value;
    $this->assertEqual($entity->field_email->value, $new_value);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->field_email->value, $new_value);

    // Test sample item generation.
    $entity = EntityTest::create();
    $entity->field_email->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

}
