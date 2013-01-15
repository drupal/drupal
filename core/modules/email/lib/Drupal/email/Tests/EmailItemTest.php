<?php

/**
 * @file
 * Contains \Drupal\email\Tests\EmailItemTest.
 */

namespace Drupal\email\Tests;

use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\email\Type\EmailItem;
use Drupal\Core\Entity\Field\FieldItemInterface;
use Drupal\Core\Entity\Field\FieldInterface;

/**
 * Tests the new entity API for the email field type.
 */
class EmailItemTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('email', 'entity_test');

  public static function getInfo() {
    return array(
      'name' => 'E-mail field item',
      'description' => 'Tests the new entity API for the email field type.',
      'group' => 'Field types',
    );
  }

  public function setUp() {
    parent::setUp();
    $this->enableModules(array('system', 'entity', 'field', 'text', 'options', 'field_sql_storage', 'entity_test', 'email'));

    // Create an email field and instance for validation.
    $this->field = array(
      'field_name' => 'field_email',
      'type' => 'email',
    );
    field_create_field($this->field);
    $this->instance = array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_email',
      'bundle' => 'entity_test',
      'widget' => array(
        'type' => 'email_default',
      ),
    );
    field_create_instance($this->instance);
  }

  /**
   * Tests using entity fields of the email field type.
   */
  public function testEmailItem() {
    // Verify entity creation.
    $entity = entity_create('entity_test', array());
    $value = 'test@example.com';
    $entity->field_email = $value;
    $entity->name->value = $this->randomName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = entity_load('entity_test', $id);
    $this->assertTrue($entity->field_email instanceof FieldInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_email[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_email->value, $value);
    $this->assertEqual($entity->field_email[0]->value, $value);

    // Verify changing the email value.
    $new_value = $this->randomName();
    $entity->field_email->value = $new_value;
    $this->assertEqual($entity->field_email->value, $new_value);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = entity_load('entity_test', $id);
    $this->assertEqual($entity->field_email->value, $new_value);
  }

}
