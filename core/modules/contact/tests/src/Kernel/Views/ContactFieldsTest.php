<?php

namespace Drupal\Tests\contact\Kernel\Views;

use Drupal\contact\Entity\ContactForm;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that no storage is created for the contact_message entity.
 *
 * @group contact
 */
class ContactFieldsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'contact',
    'field',
    'system',
    'text',
    'user',
    'views',
  ];

  /**
   * Tests the views data generation.
   */
  public function testViewsData() {
    $this->installConfig(['contact']);
    FieldStorageConfig::create([
      'type' => 'text',
      'entity_type' => 'contact_message',
      'field_name' => $field_name = strtolower($this->randomMachineName()),
    ])->save();

    ContactForm::create([
      'id' => 'contact_message',
      'label' => 'Test contact form',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'contact_message',
      'bundle' => 'contact_message',
      'field_name' => $field_name,
    ])->save();

    // Test that the field is not exposed to views, since contact_message
    // entities have no storage.
    $table_name = 'contact_message__' . $field_name;
    $data = $this->container->get('views.views_data')->get($table_name);
    $this->assertFalse($data);
  }

}
