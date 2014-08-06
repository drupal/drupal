<?php

/**
 * @file
 * Contains \Drupal\contact\Tests\Views\ContactFieldsTest.
 */

namespace Drupal\contact\Tests\Views;

use Drupal\Core\Entity\ContentEntityDatabaseStorage;
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
  protected $field_storage;

  protected function setUp() {
    parent::setUp();

    $this->field_storage = entity_create('field_storage_config', array(
      'name' => strtolower($this->randomMachineName()),
      'entity_type' => 'contact_message',
      'type' => 'text'
    ));
    $this->field_storage->save();

    entity_create('contact_category', array(
      'id' => 'contact_message',
      'label' => 'Test contact category',
    ))->save();

    entity_create('field_instance_config', array(
      'field_storage' => $this->field_storage,
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
    $table_name = ContentEntityDatabaseStorage::_fieldTableName($this->field_storage);
    $data = $this->container->get('views.views_data')->get($table_name);
    $this->assertFalse($data, 'The field is not exposed to Views.');
  }

}
