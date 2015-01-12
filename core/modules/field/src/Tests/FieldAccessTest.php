<?php

/**
 * @file
 * Definition of Drupal\field\Tests\FieldAccessTest.
 */

namespace Drupal\field\Tests;

/**
 * Tests Field access.
 *
 * @group field
 */
class FieldAccessTest extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'field_test');

  /**
   * Node entity to use in this test.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * Field value to test display on nodes.
   *
   * @var string
   */
  protected $test_view_field_value;

  protected function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('view test_view_field content'));
    $this->drupalLogin($web_user);

    // Create content type.
    $content_type_info = $this->drupalCreateContentType();
    $content_type = $content_type_info->id();

    $field_storage = array(
      'field_name' => 'test_view_field',
      'entity_type' => 'node',
      'type' => 'text',
    );
    entity_create('field_storage_config', $field_storage)->save();
    $field = array(
      'field_name' => $field_storage['field_name'],
      'entity_type' => 'node',
      'bundle' => $content_type,
    );
    entity_create('field_config', $field)->save();

    // Assign display properties for the 'default' and 'teaser' view modes.
    foreach (array('default', 'teaser') as $view_mode) {
      entity_get_display('node', $content_type, $view_mode)
        ->setComponent($field_storage['field_name'])
        ->save();
    }

    // Create test node.
    $this->test_view_field_value = 'This is some text';
    $settings = array();
    $settings['type'] = $content_type;
    $settings['title'] = 'Field view access test';
    $settings['test_view_field'] = array(array('value' => $this->test_view_field_value));
    $this->node = $this->drupalCreateNode($settings);
  }

  /**
   * Test that hook_entity_field_access() is called.
   */
  function testFieldAccess() {

    // Assert the text is visible.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertText($this->test_view_field_value);

    // Assert the text is not visible for anonymous users.
    // The field_test module implements hook_entity_field_access() which will
    // specifically target the 'test_view_field' field.
    $this->drupalLogout();
    $this->drupalGet('node/' . $this->node->id());
    $this->assertNoText($this->test_view_field_value);
  }
}
