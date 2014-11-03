<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeAccessFieldTest.
 */

namespace Drupal\node\Tests;
use Drupal\Component\Utility\Unicode;

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
  public static $modules = array('node_access_test', 'field_ui');

  /**
   * A user with permission to bypass access content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin_user;

  /**
   * A user with permission to manage content types and fields.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $content_admin_user;

  /**
   * The name of the created field.
   *
   * @var string
   */
  protected $field_name;

  protected function setUp() {
    parent::setUp();

    node_access_rebuild();

    // Create some users.
    $this->admin_user = $this->drupalCreateUser(array('access content', 'bypass node access'));
    $this->content_admin_user = $this->drupalCreateUser(array('access content', 'administer content types', 'administer node fields'));

    // Add a custom field to the page content type.
    $this->field_name = Unicode::strtolower($this->randomMachineName() . '_field_name');
    entity_create('field_storage_config', array(
      'field_name' => $this->field_name,
      'entity_type' => 'node',
      'type' => 'text'
    ))->save();
    entity_create('field_config', array(
      'field_name' => $this->field_name,
      'entity_type' => 'node',
      'bundle' => 'page',
    ))->save();
    entity_get_display('node', 'page', 'default')
      ->setComponent($this->field_name)
      ->save();
    entity_get_form_display('node', 'page', 'default')
      ->setComponent($this->field_name)
      ->save();
  }

  /**
   * Tests administering fields when node access is restricted.
   */
  function testNodeAccessAdministerField() {
    // Create a page node.
    $field_data = array();
    $value = $field_data[0]['value'] = $this->randomMachineName();
    $node = $this->drupalCreateNode(array($this->field_name => $field_data));

    // Log in as the administrator and confirm that the field value is present.
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('node/' . $node->id());
    $this->assertText($value, 'The saved field value is visible to an administrator.');

    // Log in as the content admin and try to view the node.
    $this->drupalLogin($this->content_admin_user);
    $this->drupalGet('node/' . $node->id());
    $this->assertText('Access denied', 'Access is denied for the content admin.');

    // Modify the field default as the content admin.
    $edit = array();
    $default = 'Sometimes words have two meanings';
    $edit["default_value_input[{$this->field_name}][0][value]"] = $default;
    $this->drupalPostForm(
      "admin/structure/types/manage/page/fields/node.page.{$this->field_name}",
      $edit,
      t('Save settings')
    );

    // Log in as the administrator.
    $this->drupalLogin($this->admin_user);

    // Confirm that the existing node still has the correct field value.
    $this->drupalGet('node/' . $node->id());
    $this->assertText($value, 'The original field value is visible to an administrator.');

    // Confirm that the new default value appears when creating a new node.
    $this->drupalGet('node/add/page');
    $this->assertRaw($default, 'The updated default value is displayed when creating a new node.');
  }
}
