<?php

/**
 * @file
 * Definition of Drupal\field\Tests\ActiveTest.
 */

namespace Drupal\field\Tests;

class ActiveTest extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_test', 'entity_test');

  public static function getInfo() {
    return array(
      'name' => 'Field active test',
      'description' => 'Test that fields are properly marked active or inactive.',
      'group' => 'Field API',
    );
  }

  /**
   * Test that fields are properly marked active or inactive.
   */
  function testActive() {
    $field_name = 'field_1';
    entity_create('field_entity', array(
      'name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ))->save();

    // Check that the field is correctly found.
    $field = field_read_field('entity_test', $field_name);
    $this->assertFalse(empty($field), 'The field was properly read.');

    // Disable the module providing the field type, and check that the field is
    // found only if explicitly requesting inactive fields.
    module_disable(array('field_test'));
    $field = field_read_field('entity_test', $field_name);
    $this->assertTrue(empty($field), 'The field is marked inactive when the field type is absent.');
    $field = field_read_field('entity_test', $field_name, array('include_inactive' => TRUE));
    $this->assertFalse(empty($field), 'The field is properly read when explicitly fetching inactive fields.');

    // Re-enable the module, and check that the field is active again.
    module_enable(array('field_test'));
    $field = field_read_field('entity_test', $field_name);
    $this->assertFalse(empty($field), 'The field was was marked active.');
  }
}
