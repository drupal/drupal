<?php

namespace Drupal\Tests\contact\Functional\Views;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\contact\Entity\ContactForm;

/**
 * Tests which checks that no fieldapi fields are added on contact.
 *
 * @group contact
 */
class ContactFieldsTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field', 'text', 'contact'];

  /**
   * Contains the field storage definition for contact used for this test.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => strtolower($this->randomMachineName()),
      'entity_type' => 'contact_message',
      'type' => 'text',
    ]);
    $this->fieldStorage->save();

    ContactForm::create([
      'id' => 'contact_message',
      'label' => 'Test contact form',
    ])->save();

    FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'contact_message',
    ])->save();

    $this->container->get('views.views_data')->clear();
  }

  /**
   * Tests the views data generation.
   */
  public function testViewsData() {
    // Test that the field is not exposed to views, since contact_message
    // entities have no storage.
    $table_name = 'contact_message__' . $this->fieldStorage->getName();
    $data = $this->container->get('views.views_data')->get($table_name);
    $this->assertFalse($data, 'The field is not exposed to Views.');
  }

}
