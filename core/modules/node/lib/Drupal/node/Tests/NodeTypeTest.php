<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeTypeTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests related to node types.
 */
class NodeTypeTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_ui');

  public static function getInfo() {
    return array(
      'name' => 'Node types',
      'description' => 'Ensures that node type functions work correctly.',
      'group' => 'Node',
    );
  }

  /**
   * Ensures that node type functions (node_type_get_*) work correctly.
   *
   * Load available node types and validate the returned data.
   */
  function testNodeTypeGetFunctions() {
    $node_types = node_type_get_types();
    $node_names = node_type_get_names();

    $this->assertTrue(isset($node_types['article']), 'Node type article is available.');
    $this->assertTrue(isset($node_types['page']), 'Node type basic page is available.');

    $this->assertEqual($node_types['article']->name, $node_names['article'], 'Correct node type base has been returned.');

    $this->assertEqual($node_types['article'], node_type_load('article'), 'Correct node type has been returned.');
    $this->assertEqual($node_types['article']->name, node_type_get_label('article'), 'Correct node type name has been returned.');
    $this->assertEqual($node_types['page']->base, node_type_get_base('page'), 'Correct node type base has been returned.');
  }

  /**
   * Tests creating a content type programmatically and via a form.
   */
  function testNodeTypeCreation() {
    // Create a content type programmaticaly.
    $type = $this->drupalCreateContentType();

    $type_exists = db_query('SELECT 1 FROM {node_type} WHERE type = :type', array(':type' => $type->type))->fetchField();
    $this->assertTrue($type_exists, 'The new content type has been created in the database.');

    // Login a test user.
    $web_user = $this->drupalCreateUser(array('create ' . $type->name . ' content'));
    $this->drupalLogin($web_user);

    $this->drupalGet('node/add/' . $type->type);
    $this->assertResponse(200, 'The new content type can be accessed at node/add.');

    // Create a content type via the user interface.
    $web_user = $this->drupalCreateUser(array('bypass node access', 'administer content types'));
    $this->drupalLogin($web_user);
    $edit = array(
      'name' => 'foo',
      'title_label' => 'title for foo',
      'type' => 'foo',
    );
    $this->drupalPost('admin/structure/types/add', $edit, t('Save content type'));
    $type_exists = db_query('SELECT 1 FROM {node_type} WHERE type = :type', array(':type' => 'foo'))->fetchField();
    $this->assertTrue($type_exists, 'The new content type has been created in the database.');
  }

  /**
   * Tests editing a node type using the UI.
   */
  function testNodeTypeEditing() {
    $web_user = $this->drupalCreateUser(array('bypass node access', 'administer content types', 'administer node fields'));
    $this->drupalLogin($web_user);

    $instance = field_info_instance('node', 'body', 'page');
    $this->assertEqual($instance['label'], 'Body', 'Body field was found.');

    // Verify that title and body fields are displayed.
    $this->drupalGet('node/add/page');
    $this->assertRaw('Title', 'Title field was found.');
    $this->assertRaw('Body', 'Body field was found.');

    // Rename the title field.
    $edit = array(
      'title_label' => 'Foo',
    );
    $this->drupalPost('admin/structure/types/manage/page', $edit, t('Save content type'));
    // Refresh the field information for the rest of the test.
    field_info_cache_clear();

    $this->drupalGet('node/add/page');
    $this->assertRaw('Foo', 'New title label was displayed.');
    $this->assertNoRaw('Title', 'Old title label was not displayed.');

    // Change the name, machine name and description.
    $edit = array(
      'name' => 'Bar',
      'type' => 'bar',
      'description' => 'Lorem ipsum.',
    );
    $this->drupalPost('admin/structure/types/manage/page', $edit, t('Save content type'));
    field_info_cache_clear();

    $this->drupalGet('node/add');
    $this->assertRaw('Bar', 'New name was displayed.');
    $this->assertRaw('Lorem ipsum', 'New description was displayed.');
    $this->clickLink('Bar');
    $this->assertEqual(url('node/add/bar', array('absolute' => TRUE)), $this->getUrl(), 'New machine name was used in URL.');
    $this->assertRaw('Foo', 'Title field was found.');
    $this->assertRaw('Body', 'Body field was found.');

    // Remove the body field.
    $this->drupalPost('admin/structure/types/manage/bar/fields/node.bar.body/delete', array(), t('Delete'));
    // Resave the settings for this type.
    $this->drupalPost('admin/structure/types/manage/bar', array(), t('Save content type'));
    // Check that the body field doesn't exist.
    $this->drupalGet('node/add/bar');
    $this->assertNoRaw('Body', 'Body field was not found.');
  }

  /**
   * Tests that node_types_rebuild() correctly handles the 'disabled' flag.
   */
  function testNodeTypeStatus() {
    // Enable all core node modules, and all types should be active.
    module_enable(array('book'), FALSE);
    node_types_rebuild();
    $types = node_type_get_types();
    foreach (array('book', 'article', 'page') as $type) {
      $this->assertTrue(isset($types[$type]), format_string('%type is found in node types.', array('%type' => $type)));
      $this->assertTrue(isset($types[$type]->disabled) && empty($types[$type]->disabled), format_string('%type type is enabled.', array('%type' => $type)));
    }

    // Disable book module and the respective type should still be active, since
    // it is not provided by hook_node_info().
    module_disable(array('book'), FALSE);
    node_types_rebuild();
    $types = node_type_get_types();
    $this->assertTrue(isset($types['book']) && empty($types['book']->disabled), "Book module's node type still active.");
    $this->assertTrue(isset($types['article']) && empty($types['article']->disabled), 'Article node type still active.');
    $this->assertTrue(isset($types['page']) && empty($types['page']->disabled), 'Basic page node type still active.');

    // Re-enable the modules and verify that the types are active again.
    module_enable(array('book'), FALSE);
    node_types_rebuild();
    $types = node_type_get_types();
    foreach (array('book', 'article', 'page') as $type) {
      $this->assertTrue(isset($types[$type]), format_string('%type is found in node types.', array('%type' => $type)));
      $this->assertTrue(isset($types[$type]->disabled) && empty($types[$type]->disabled), format_string('%type type is enabled.', array('%type' => $type)));
    }
  }

  /**
   * Tests deleting a content type that still has content.
   */
  function testNodeTypeDeletion() {
    // Create a content type programmatically.
    $type = $this->drupalCreateContentType();

    // Log in a test user.
    $web_user = $this->drupalCreateUser(array(
      'bypass node access',
      'administer content types',
    ));
    $this->drupalLogin($web_user);

    // Add a new node of this type.
    $node = $this->drupalCreateNode(array('type' => $type->type));
    // Attempt to delete the content type, which should not be allowed.
    $this->drupalGet('admin/structure/types/manage/' . $type->name . '/delete');
    $this->assertRaw(
      t('%type is used by 1 piece of content on your site. You can not remove this content type until you have removed all of the %type content.', array('%type' => $type->name)),
      'The content type will not be deleted until all nodes of that type are removed.'
    );
    $this->assertNoText(t('This action cannot be undone.'), 'The node type deletion confirmation form is not available.');

    // Delete the node.
    $node->delete();
    // Attempt to delete the content type, which should now be allowed.
    $this->drupalGet('admin/structure/types/manage/' . $type->name . '/delete');
    $this->assertRaw(
      t('Are you sure you want to delete the content type %type?', array('%type' => $type->name)),
      'The content type is available for deletion.'
    );
    $this->assertText(t('This action cannot be undone.'), 'The node type deletion confirmation form is available.');
  }

}
