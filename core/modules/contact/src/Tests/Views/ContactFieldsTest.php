<?php

/**
 * @file
 * Contains \Drupal\contact\Tests\Views\ContactFieldsTest.
 */

namespace Drupal\contact\Tests\Views;

use Drupal\views\Tests\ViewTestBase;

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
  public static $modules = array('field', 'text', 'contact');

  /**
   * Contains the field storage definition for contact used for this test.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  protected function setUp() {
    parent::setUp();

    $this->fieldStorage = entity_create('field_storage_config', array(
      'field_name' => strtolower($this->randomMachineName()),
      'entity_type' => 'contact_message',
      'type' => 'text'
    ));
    $this->fieldStorage->save();

    entity_create('contact_form', array(
      'id' => 'contact_message',
      'label' => 'Test contact form',
    ))->save();

    entity_create('field_config', array(
      'field_storage' => $this->fieldStorage,
      'bundle' => 'contact_message',
    ))->save();

    $this->container->get('views.views_data')->clear();
  }

  /**
   * Tests the views data generation.
   */
  public function testViewsData() {
    // Test that the field is not exposed to views, since contact_message
    // entities have no storage.
    $table_name = 'contact_message__' .  $this->fieldStorage->getName();
    $data = $this->container->get('views.views_data')->get($table_name);
    $this->assertFalse($data, 'The field is not exposed to Views.');
  }

}
