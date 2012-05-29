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
  public static function getInfo() {
    return array(
      'name' => 'Node types',
      'description' => 'Ensures that node type functions work correctly.',
      'group' => 'Node',
    );
  }

  function setUp() {
    parent::setUp(array('field_ui'));
  }

  /**
   * Ensure that node type functions (node_type_get_*) work correctly.
   *
   * Load available node types and validate the returned data.
   */
  function testNodeTypeGetFunctions() {
    $node_types = node_type_get_types();
    $node_names = node_type_get_names();

    $this->assertTrue(isset($node_types['article']), t('Node type article is available.'));
    $this->assertTrue(isset($node_types['page']), t('Node type basic page is available.'));

    $this->assertEqual($node_types['article']->name, $node_names['article'], t('Correct node type base has been returned.'));

    $this->assertEqual($node_types['article'], node_type_load('article'), t('Correct node type has been returned.'));
    $this->assertEqual($node_types['article']->name, node_type_get_name('article'), t('Correct node type name has been returned.'));
    $this->assertEqual($node_types['page']->base, node_type_get_base('page'), t('Correct node type base has been returned.'));
  }

  /**
   * Test creating a content type programmatically and via a form.
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
   * Test editing a node type using the UI.
   */
  function testNodeTypeEditing() {
    $web_user = $this->drupalCreateUser(array('bypass node access', 'administer content types'));
    $this->drupalLogin($web_user);

    $instance = field_info_instance('node', 'body', 'page');
    $this->assertEqual($instance['label'], 'Body', t('Body field was found.'));

    // Verify that title and body fields are displayed.
    $this->drupalGet('node/add/page');
    $this->assertRaw('Title', t('Title field was found.'));
    $this->assertRaw('Body', t('Body field was found.'));

    // Rename the title field.
    $edit = array(
      'title_label' => 'Foo',
    );
    $this->drupalPost('admin/structure/types/manage/page', $edit, t('Save content type'));
    // Refresh the field information for the rest of the test.
    field_info_cache_clear();

    $this->drupalGet('node/add/page');
    $this->assertRaw('Foo', t('New title label was displayed.'));
    $this->assertNoRaw('Title', t('Old title label was not displayed.'));

    // Change the name, machine name and description.
    $edit = array(
      'name' => 'Bar',
      'type' => 'bar',
      'description' => 'Lorem ipsum.',
    );
    $this->drupalPost('admin/structure/types/manage/page', $edit, t('Save content type'));
    field_info_cache_clear();

    $this->drupalGet('node/add');
    $this->assertRaw('Bar', t('New name was displayed.'));
    $this->assertRaw('Lorem ipsum', t('New description was displayed.'));
    $this->clickLink('Bar');
    $this->assertEqual(url('node/add/bar', array('absolute' => TRUE)), $this->getUrl(), t('New machine name was used in URL.'));
    $this->assertRaw('Foo', t('Title field was found.'));
    $this->assertRaw('Body', t('Body field was found.'));

    // Remove the body field.
    $this->drupalPost('admin/structure/types/manage/bar/fields/body/delete', array(), t('Delete'));
    // Resave the settings for this type.
    $this->drupalPost('admin/structure/types/manage/bar', array(), t('Save content type'));
    // Check that the body field doesn't exist.
    $this->drupalGet('node/add/bar');
    $this->assertNoRaw('Body', t('Body field was not found.'));
  }

  /**
   * Test that node_types_rebuild() correctly handles the 'disabled' flag.
   */
  function testNodeTypeStatus() {
    // Enable all core node modules, and all types should be active.
    module_enable(array('book', 'poll'), FALSE);
    node_types_rebuild();
    $types = node_type_get_types();
    foreach (array('book', 'poll', 'article', 'page') as $type) {
      $this->assertTrue(isset($types[$type]), t('%type is found in node types.', array('%type' => $type)));
      $this->assertTrue(isset($types[$type]->disabled) && empty($types[$type]->disabled), t('%type type is enabled.', array('%type' => $type)));
    }

    // Disable poll module and the respective type should be marked as disabled.
    module_disable(array('poll'), FALSE);
    node_types_rebuild();
    $types = node_type_get_types();
    $this->assertTrue(!empty($types['poll']->disabled), t("Poll module's node type disabled."));

    // Disable book module and the respective type should still be active, since
    // it is not provided by hook_node_info().
    module_disable(array('book'), FALSE);
    node_types_rebuild();
    $types = node_type_get_types();
    $this->assertTrue(isset($types['book']) && empty($types['book']->disabled), t("Book module's node type still active."));
    $this->assertTrue(!empty($types['poll']->disabled), t("Poll module's node type still disabled."));
    $this->assertTrue(isset($types['article']) && empty($types['article']->disabled), t("Article node type still active."));
    $this->assertTrue(isset($types['page']) && empty($types['page']->disabled), t("Basic page node type still active."));

    // Re-enable the modules and verify that the types are active again.
    module_enable(array('book', 'poll'), FALSE);
    node_types_rebuild();
    $types = node_type_get_types();
    foreach (array('book', 'poll', 'article', 'page') as $type) {
      $this->assertTrue(isset($types[$type]), t('%type is found in node types.', array('%type' => $type)));
      $this->assertTrue(isset($types[$type]->disabled) && empty($types[$type]->disabled), t('%type type is enabled.', array('%type' => $type)));
    }
  }
}
