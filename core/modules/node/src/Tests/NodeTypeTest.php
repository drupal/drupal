<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeTypeTest.
 */

namespace Drupal\node\Tests;
use Drupal\field\Entity\FieldConfig;

/**
 * Ensures that node type functions work correctly.
 *
 * @group node
 */
class NodeTypeTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_ui');

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

    $article = entity_load('node_type', 'article');
    $this->assertEqual($node_types['article'], $article, 'Correct node type has been returned.');
    $this->assertEqual($node_types['article']->name, $article->label(), 'Correct node type name has been returned.');
  }

  /**
   * Tests creating a content type programmatically and via a form.
   */
  function testNodeTypeCreation() {
    // Create a content type programmaticaly.
    $type = $this->drupalCreateContentType();

    $type_exists = (bool) entity_load('node_type', $type->type);
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
    $this->drupalPostForm('admin/structure/types/add', $edit, t('Save and manage fields'));
    $type_exists = (bool) entity_load('node_type', 'foo');
    $this->assertTrue($type_exists, 'The new content type has been created in the database.');
  }

  /**
   * Tests editing a node type using the UI.
   */
  function testNodeTypeEditing() {
    $web_user = $this->drupalCreateUser(array('bypass node access', 'administer content types', 'administer node fields'));
    $this->drupalLogin($web_user);

    $field = FieldConfig::loadByName('node', 'page', 'body');
    $this->assertEqual($field->getLabel(), 'Body', 'Body field was found.');

    // Verify that title and body fields are displayed.
    $this->drupalGet('node/add/page');
    $this->assertRaw('Title', 'Title field was found.');
    $this->assertRaw('Body', 'Body field was found.');

    // Rename the title field.
    $edit = array(
      'title_label' => 'Foo',
    );
    $this->drupalPostForm('admin/structure/types/manage/page', $edit, t('Save content type'));

    $this->drupalGet('node/add/page');
    $this->assertRaw('Foo', 'New title label was displayed.');
    $this->assertNoRaw('Title', 'Old title label was not displayed.');

    // Change the name, machine name and description.
    $edit = array(
      'name' => 'Bar',
      'type' => 'bar',
      'description' => 'Lorem ipsum.',
    );
    $this->drupalPostForm('admin/structure/types/manage/page', $edit, t('Save content type'));

    $this->drupalGet('node/add');
    $this->assertRaw('Bar', 'New name was displayed.');
    $this->assertRaw('Lorem ipsum', 'New description was displayed.');
    $this->clickLink('Bar');
    $this->assertEqual(url('node/add/bar', array('absolute' => TRUE)), $this->getUrl(), 'New machine name was used in URL.');
    $this->assertRaw('Foo', 'Title field was found.');
    $this->assertRaw('Body', 'Body field was found.');

    // Remove the body field.
    $this->drupalPostForm('admin/structure/types/manage/bar/fields/node.bar.body/delete', array(), t('Delete'));
    // Resave the settings for this type.
    $this->drupalPostForm('admin/structure/types/manage/bar', array(), t('Save content type'));
    // Check that the body field doesn't exist.
    $this->drupalGet('node/add/bar');
    $this->assertNoRaw('Body', 'Body field was not found.');
  }

  /**
   * Tests that node types correctly handles their locking.
   */
  function testNodeTypeStatus() {
    // Enable all core node modules, and all types should be active.
    $this->container->get('module_handler')->install(array('book'), FALSE);
    $types = node_type_get_types();
    foreach (array('book', 'article', 'page') as $type) {
      $this->assertTrue(isset($types[$type]), format_string('%type is found in node types.', array('%type' => $type)));
      $this->assertFalse($types[$type]->isLocked(), format_string('%type type is not locked.', array('%type' => $type)));
    }

    // Disable book module and the respective type should still be active, since
    // it is not provided by shipped configuration entity.
    $this->container->get('module_handler')->uninstall(array('book'), FALSE);
    $types = node_type_get_types();
    $this->assertFalse($types['book']->isLocked(), "Book module's node type still active.");
    $this->assertFalse($types['article']->isLocked(), 'Article node type still active.');
    $this->assertFalse($types['page']->isLocked(), 'Basic page node type still active.');

    // Re-install the modules and verify that the types are active again.
    $this->container->get('module_handler')->install(array('book'), FALSE);
    $types = node_type_get_types();
    foreach (array('book', 'article', 'page') as $type) {
      $this->assertTrue(isset($types[$type]), format_string('%type is found in node types.', array('%type' => $type)));
      $this->assertFalse($types[$type]->isLocked(), format_string('%type type is not locked.', array('%type' => $type)));
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
    // Test that forum node type could not be deleted while forum active.
    $this->container->get('module_handler')->install(array('forum'));
    // Call to flush all caches after installing the forum module in the same
    // way installing a module through the UI does.
    $this->resetAll();
    $this->drupalGet('admin/structure/types/manage/forum');
    $this->assertNoLink(t('Delete'));
    $this->drupalGet('admin/structure/types/manage/forum/delete');
    $this->assertResponse(403);
    $this->container->get('module_handler')->uninstall(array('forum'));
    $this->drupalGet('admin/structure/types/manage/forum');
    $this->assertLink(t('Delete'));
    $this->drupalGet('admin/structure/types/manage/forum/delete');
    $this->assertResponse(200);
  }

  /**
   * Tests Field UI integration for content types.
   */
  public function testNodeTypeFieldUiPermissions() {
    // Create an admin user who can only manage node fields.
    $admin_user_1 = $this->drupalCreateUser(array('administer content types', 'administer node fields'));
    $this->drupalLogin($admin_user_1);

    // Test that the user only sees the actions available to him.
    $this->drupalGet('admin/structure/types');
    $this->assertLinkByHref('admin/structure/types/manage/article/fields');
    $this->assertNoLinkByHref('admin/structure/types/manage/article/display');

    // Create another admin user who can manage node fields display.
    $admin_user_2 = $this->drupalCreateUser(array('administer content types', 'administer node display'));
    $this->drupalLogin($admin_user_2);

    // Test that the user only sees the actions available to him.
    $this->drupalGet('admin/structure/types');
    $this->assertNoLinkByHref('admin/structure/types/manage/article/fields');
    $this->assertLinkByHref('admin/structure/types/manage/article/display');
  }

}
