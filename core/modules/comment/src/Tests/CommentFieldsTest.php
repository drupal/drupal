<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\CommentFieldsTest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\comment\Entity\CommentType;

/**
 * Tests fields on comments.
 *
 * @group comment
 */
class CommentFieldsTest extends CommentTestBase {

  /**
   * Install the field UI.
   *
   * @var array
   */
  public static $modules = array('field_ui');

  /**
   * Tests that the default 'comment_body' field is correctly added.
   */
  function testCommentDefaultFields() {
    // Do not make assumptions on default node types created by the test
    // installation profile, and create our own.
    $this->drupalCreateContentType(array('type' => 'test_node_type'));
    $this->addDefaultCommentField('node', 'test_node_type');

    // Check that the 'comment_body' field is present on the comment bundle.
    $field = FieldConfig::loadByName('comment', 'comment', 'comment_body');
    $this->assertTrue(!empty($field), 'The comment_body field is added when a comment bundle is created');

    $field->delete();

    // Check that the 'comment_body' field is not deleted since it is persisted
    // even if it has no fields.
    $field_storage = FieldStorageConfig::loadByName('comment', 'comment_body');
    $this->assertTrue($field_storage, 'The comment_body field storage was not deleted');

    // Create a new content type.
    $type_name = 'test_node_type_2';
    $this->drupalCreateContentType(array('type' => $type_name));
    $this->addDefaultCommentField('node', $type_name);

    // Check that the 'comment_body' field exists and has an instance on the
    // new comment bundle.
    $field_storage = FieldStorageConfig::loadByName('comment', 'comment_body');
    $this->assertTrue($field_storage, 'The comment_body field exists');
    $field = FieldConfig::loadByName('comment', 'comment', 'comment_body');
    $this->assertTrue(isset($field), format_string('The comment_body field is present for comments on type @type', array('@type' => $type_name)));

    // Test adding a field that defaults to CommentItemInterface::CLOSED.
    $this->addDefaultCommentField('node', 'test_node_type', 'who_likes_ponies', CommentItemInterface::CLOSED, 'who_likes_ponies');
    $field = FieldConfig::load('node.test_node_type.who_likes_ponies');
    $this->assertEqual($field->default_value[0]['status'], CommentItemInterface::CLOSED);
  }

  /**
   * Tests that you can remove a comment field.
   */
  public function testCommentFieldDelete() {
    $this->drupalCreateContentType(array('type' => 'test_node_type'));
    $this->addDefaultCommentField('node', 'test_node_type');
    // We want to test the handling of removing the primary comment field, so we
    // ensure there is at least one other comment field attached to a node type
    // so that comment_entity_load() runs for nodes.
    $this->addDefaultCommentField('node', 'test_node_type', 'comment2');

    // Create a sample node.
    $node = $this->drupalCreateNode(array(
      'title' => 'Baloney',
      'type' => 'test_node_type',
    ));

    $this->drupalLogin($this->webUser);

    $this->drupalGet('node/' . $node->nid->value);
    $elements = $this->cssSelect('.field--type-comment');
    $this->assertEqual(2, count($elements), 'There are two comment fields on the node.');

    // Delete the first comment field.
    FieldStorageConfig::loadByName('node', 'comment')->delete();
    $this->drupalGet('node/' . $node->nid->value);
    $elements = $this->cssSelect('.field--type-comment');
    $this->assertEqual(1, count($elements), 'There is one comment field on the node.');
  }

  /**
   * Tests creating a comment field through the interface.
   */
  public function testCommentFieldCreate() {
    // Create user who can administer user fields.
    $user = $this->drupalCreateUser(array(
      'administer user fields',
    ));
    $this->drupalLogin($user);

    // Create comment field in account settings.
    $edit = array(
      'new_storage_type' => 'comment',
      'label' => 'User comment',
      'field_name' => 'user_comment',
    );
    $this->drupalPostForm('admin/config/people/accounts/fields/add-field', $edit, 'Save and continue');

    // Try to save the comment field without selecting a comment type.
    $edit = array();
    $this->drupalPostForm('admin/config/people/accounts/fields/user.user.field_user_comment/storage', $edit, t('Save field settings'));
    // We should get an error message.
    $this->assertText(t('An illegal choice has been detected. Please contact the site administrator.'));

    // Create a comment type for users.
    $bundle = CommentType::create(array(
      'id' => 'user_comment_type',
      'label' => 'user_comment_type',
      'description' => '',
      'target_entity_type_id' => 'user',
    ));
    $bundle->save();

    // Select a comment type and try to save again.
    $edit = array(
      'settings[comment_type]' => 'user_comment_type',
    );
    $this->drupalPostForm('admin/config/people/accounts/fields/user.user.field_user_comment/storage', $edit, t('Save field settings'));
    // We shouldn't get an error message.
    $this->assertNoText(t('An illegal choice has been detected. Please contact the site administrator.'));
  }

  /**
   * Tests that comment module works when installed after a content module.
   */
  function testCommentInstallAfterContentModule() {
    // Create a user to do module administration.
    $this->adminUser = $this->drupalCreateUser(array('access administration pages', 'administer modules'));
    $this->drupalLogin($this->adminUser);

    // Drop default comment field added in CommentTestBase::setup().
    FieldStorageConfig::loadByName('node', 'comment')->delete();
    if ($field_storage = FieldStorageConfig::loadByName('node', 'comment_forum')) {
      $field_storage->delete();
    }

    // Purge field data now to allow comment module to be uninstalled once the
    // field has been deleted.
    field_purge_batch(10);

    // Uninstall the comment module.
    $edit = array();
    $edit['uninstall[comment]'] = TRUE;
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->drupalPostForm(NULL, array(), t('Uninstall'));
    $this->rebuildContainer();
    $this->assertFalse($this->container->get('module_handler')->moduleExists('comment'), 'Comment module uninstalled.');

    // Install core content type module (book).
    $edit = array();
    $edit['modules[Core][book][enable]'] = 'book';
    $this->drupalPostForm('admin/modules', $edit, t('Install'));

    // Now install the comment module.
    $edit = array();
    $edit['modules[Core][comment][enable]'] = 'comment';
    $this->drupalPostForm('admin/modules', $edit, t('Install'));
    $this->rebuildContainer();
    $this->assertTrue($this->container->get('module_handler')->moduleExists('comment'), 'Comment module enabled.');

    // Create nodes of each type.
    $this->addDefaultCommentField('node', 'book');
    $book_node = $this->drupalCreateNode(array('type' => 'book'));

    $this->drupalLogout();

    // Try to post a comment on each node. A failure will be triggered if the
    // comment body is missing on one of these forms, due to postComment()
    // asserting that the body is actually posted correctly.
    $this->webUser = $this->drupalCreateUser(array('access content', 'access comments', 'post comments', 'skip comment approval'));
    $this->drupalLogin($this->webUser);
    $this->postComment($book_node, $this->randomMachineName(), $this->randomMachineName());
  }

}
