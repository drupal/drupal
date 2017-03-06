<?php

namespace Drupal\Tests\field\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

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
  public static $modules = ['node', 'field_test'];

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
  protected $testViewFieldValue;

  protected function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(['view test_view_field content']);
    $this->drupalLogin($web_user);

    // Create content type.
    $content_type_info = $this->drupalCreateContentType();
    $content_type = $content_type_info->id();

    $field_storage = [
      'field_name' => 'test_view_field',
      'entity_type' => 'node',
      'type' => 'text',
    ];
    FieldStorageConfig::create($field_storage)->save();
    $field = [
      'field_name' => $field_storage['field_name'],
      'entity_type' => 'node',
      'bundle' => $content_type,
    ];
    FieldConfig::create($field)->save();

    // Assign display properties for the 'default' and 'teaser' view modes.
    foreach (['default', 'teaser'] as $view_mode) {
      entity_get_display('node', $content_type, $view_mode)
        ->setComponent($field_storage['field_name'])
        ->save();
    }

    // Create test node.
    $this->testViewFieldValue = 'This is some text';
    $settings = [];
    $settings['type'] = $content_type;
    $settings['title'] = 'Field view access test';
    $settings['test_view_field'] = [['value' => $this->testViewFieldValue]];
    $this->node = $this->drupalCreateNode($settings);
  }

  /**
   * Test that hook_entity_field_access() is called.
   */
  public function testFieldAccess() {

    // Assert the text is visible.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertText($this->testViewFieldValue);

    // Assert the text is not visible for anonymous users.
    // The field_test module implements hook_entity_field_access() which will
    // specifically target the 'test_view_field' field.
    $this->drupalLogout();
    $this->drupalGet('node/' . $this->node->id());
    $this->assertNoText($this->testViewFieldValue);
  }

}
