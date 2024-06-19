<?php

declare(strict_types=1);

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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an email field storage and field for validation.
    FieldStorageConfig::create([
      'field_name' => 'field_email',
      'entity_type' => 'entity_test',
      'type' => 'email',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_email',
      'bundle' => 'entity_test',
    ])->save();

    // Create a form display for the default form mode.
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('entity_test', 'entity_test')
      ->setComponent('field_email', [
        'type' => 'email_default',
      ])
      ->save();
  }

  /**
   * Tests using entity fields of the email field type.
   */
  public function testEmailItem(): void {
    // Verify entity creation.
    $entity = EntityTest::create();
    $value = 'test@example.com';
    $entity->field_email = $value;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->field_email);
    $this->assertInstanceOf(FieldItemInterface::class, $entity->field_email[0]);
    $this->assertEquals($value, $entity->field_email->value);
    $this->assertEquals($value, $entity->field_email[0]->value);

    // Verify changing the email value.
    $new_value = $this->randomMachineName();
    $entity->field_email->value = $new_value;
    $this->assertEquals($new_value, $entity->field_email->value);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = EntityTest::load($id);
    $this->assertEquals($new_value, $entity->field_email->value);

    // Test sample item generation.
    $entity = EntityTest::create();
    $entity->field_email->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

}
