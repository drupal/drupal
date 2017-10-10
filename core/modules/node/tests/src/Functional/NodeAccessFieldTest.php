<?php

namespace Drupal\Tests\node\Functional;

use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the interaction of the node access system with fields.
 *
 * @group node
 */
class NodeAccessFieldTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node_access_test', 'field_ui'];

  /**
   * A user with permission to bypass access content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user with permission to manage content types and fields.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $contentAdminUser;

  /**
   * The name of the created field.
   *
   * @var string
   */
  protected $fieldName;

  protected function setUp() {
    parent::setUp();

    node_access_rebuild();

    // Create some users.
    $this->adminUser = $this->drupalCreateUser(['access content', 'bypass node access']);
    $this->contentAdminUser = $this->drupalCreateUser(['access content', 'administer content types', 'administer node fields']);

    // Add a custom field to the page content type.
    $this->fieldName = Unicode::strtolower($this->randomMachineName() . '_field_name');
    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'text'
    ])->save();
    FieldConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'bundle' => 'page',
    ])->save();
    entity_get_display('node', 'page', 'default')
      ->setComponent($this->fieldName)
      ->save();
    entity_get_form_display('node', 'page', 'default')
      ->setComponent($this->fieldName)
      ->save();
  }

  /**
   * Tests administering fields when node access is restricted.
   */
  public function testNodeAccessAdministerField() {
    // Create a page node.
    $fieldData = [];
    $value = $fieldData[0]['value'] = $this->randomMachineName();
    $node = $this->drupalCreateNode([$this->fieldName => $fieldData]);

    // Log in as the administrator and confirm that the field value is present.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/' . $node->id());
    $this->assertText($value, 'The saved field value is visible to an administrator.');

    // Log in as the content admin and try to view the node.
    $this->drupalLogin($this->contentAdminUser);
    $this->drupalGet('node/' . $node->id());
    $this->assertText('Access denied', 'Access is denied for the content admin.');

    // Modify the field default as the content admin.
    $edit = [];
    $default = 'Sometimes words have two meanings';
    $edit["default_value_input[{$this->fieldName}][0][value]"] = $default;
    $this->drupalPostForm(
      "admin/structure/types/manage/page/fields/node.page.{$this->fieldName}",
      $edit,
      t('Save settings')
    );

    // Log in as the administrator.
    $this->drupalLogin($this->adminUser);

    // Confirm that the existing node still has the correct field value.
    $this->drupalGet('node/' . $node->id());
    $this->assertText($value, 'The original field value is visible to an administrator.');

    // Confirm that the new default value appears when creating a new node.
    $this->drupalGet('node/add/page');
    $this->assertRaw($default, 'The updated default value is displayed when creating a new node.');
  }

}
