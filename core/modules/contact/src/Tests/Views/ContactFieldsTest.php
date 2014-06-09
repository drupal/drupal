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
 */
class ContactFieldsTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field', 'text', 'contact');

  /**
   * Contains the field definition array attached to contact used for this test.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  public static function getInfo() {
    return array(
      'name' => 'Contact: Field views data',
      'description' => 'Tests which checks that no fieldapi fields are added on contact.',
      'group' => 'Views module integration',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->field = entity_create('field_config', array(
      'name' => strtolower($this->randomName()),
      'entity_type' => 'contact_message',
      'type' => 'text'
    ));
    $this->field->save();

    entity_create('contact_category', array(
      'id' => 'contact_message',
      'label' => 'Test contact category',
    ))->save();

    entity_create('field_instance_config', array(
      'field' => $this->field,
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
    $table_name = ContentEntityDatabaseStorage::_fieldTableName($this->field);
    $data = $this->container->get('views.views_data')->get($table_name);
    $this->assertFalse($data, 'The field is not exposed to Views.');
  }

}
