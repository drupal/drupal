<?php

/**
 * @file
 * Definition of Drupal\field\Tests\FieldAccessTest.
 */

namespace Drupal\field\Tests;

/**
 * Tests the functionality of field access.
 */
class FieldAccessTest extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'field_test');

  public static function getInfo() {
    return array(
      'name' => 'Field access tests',
      'description' => 'Test Field access.',
      'group' => 'Field API',
    );
  }

  function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('view test_view_field content'));
    $this->drupalLogin($web_user);

    // Create content type.
    $this->content_type_info = $this->drupalCreateContentType();
    $this->content_type = $this->content_type_info->type;

    $this->field = array(
      'field_name' => 'test_view_field',
      'type' => 'text',
    );
    field_create_field($this->field);
    $this->instance = array(
      'field_name' => $this->field['field_name'],
      'entity_type' => 'node',
      'bundle' => $this->content_type,
    );
    field_create_instance($this->instance);

    // Assign display properties for the 'default' and 'teaser' view modes.
    foreach (array('default', 'teaser') as $view_mode) {
      entity_get_display('node', $this->content_type, $view_mode)
        ->setComponent($this->field['field_name'])
        ->save();
    }

    // Create test node.
    $this->test_view_field_value = 'This is some text';
    $settings = array();
    $settings['type'] = $this->content_type;
    $settings['title'] = 'Field view access test';
    $settings['test_view_field'] = array(array('value' => $this->test_view_field_value));
    $this->node = $this->drupalCreateNode($settings);
  }

  /**
   * Test that hook_field_access() is called.
   */
  function testFieldAccess() {

    // Assert the text is visible.
    $this->drupalGet('node/' . $this->node->nid);
    $this->assertText($this->test_view_field_value);

    // Assert the text is not visible for anonymous users.
    // The field_test module implements hook_field_access() which will
    // specifically target the 'test_view_field' field.
    $this->drupalLogout();
    $this->drupalGet('node/' . $this->node->nid);
    $this->assertNoText($this->test_view_field_value);
  }
}
