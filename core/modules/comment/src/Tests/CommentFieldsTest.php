<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentFieldsTest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldInstanceConfig;

/**
 * Tests fields on comments.
 *
 * @group comment
 */
class CommentFieldsTest extends CommentTestBase {

  /**
   * Enable the field UI.
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
    $this->container->get('comment.manager')->addDefaultField('node', 'test_node_type');

    // Check that the 'comment_body' field is present on the comment bundle.
    $instance = FieldInstanceConfig::loadByName('comment', 'comment', 'comment_body');
    $this->assertTrue(!empty($instance), 'The comment_body field is added when a comment bundle is created');

    $instance->delete();

    // Check that the 'comment_body' field is deleted.
    $field_storage = FieldStorageConfig::loadByName('comment', 'comment_body');
    $this->assertTrue(empty($field_storage), 'The comment_body field was deleted');

    // Create a new content type.
    $type_name = 'test_node_type_2';
    $this->drupalCreateContentType(array('type' => $type_name));
    $this->container->get('comment.manager')->addDefaultField('node', $type_name);

    // Check that the 'comment_body' field exists and has an instance on the
    // new comment bundle.
    $field_storage = FieldStorageConfig::loadByName('comment', 'comment_body');
    $this->assertTrue($field_storage, 'The comment_body field exists');
    $instance = FieldInstanceConfig::loadByName('comment', 'comment', 'comment_body');
    $this->assertTrue(isset($instance), format_string('The comment_body field is present for comments on type @type', array('@type' => $type_name)));

    // Test adding a field that defaults to CommentItemInterface::CLOSED.
    $this->container->get('comment.manager')->addDefaultField('node', 'test_node_type', 'who_likes_ponies', CommentItemInterface::CLOSED, 'who_likes_ponies');
    $field_storage = entity_load('field_instance_config', 'node.test_node_type.who_likes_ponies');
    $this->assertEqual($field_storage->default_value[0]['status'], CommentItemInterface::CLOSED);
  }

  /**
   * Tests that comment module works when installed after a content module.
   */
  function testCommentInstallAfterContentModule() {
    // Create a user to do module administration.
    $this->admin_user = $this->drupalCreateUser(array('access administration pages', 'administer modules'));
    $this->drupalLogin($this->admin_user);

    // Drop default comment field added in CommentTestBase::setup().
    FieldStorageConfig::loadByName('node', 'comment')->delete();
    if ($field_storage = FieldStorageConfig::loadByName('node', 'comment_forum')) {
      $field_storage->delete();
    }

    // Purge field data now to allow comment module to be uninstalled once the
    // field has been deleted.
    field_purge_batch(10);

    // Disable the comment module.
    $edit = array();
    $edit['uninstall[comment]'] = TRUE;
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->drupalPostForm(NULL, array(), t('Uninstall'));
    $this->rebuildContainer();
    $this->assertFalse($this->container->get('module_handler')->moduleExists('comment'), 'Comment module uninstalled.');

    // Enable core content type module (book).
    $edit = array();
    $edit['modules[Core][book][enable]'] = 'book';
    $this->drupalPostForm('admin/modules', $edit, t('Save configuration'));

    // Now install the comment module.
    $edit = array();
    $edit['modules[Core][comment][enable]'] = 'comment';
    $this->drupalPostForm('admin/modules', $edit, t('Save configuration'));
    $this->rebuildContainer();
    $this->assertTrue($this->container->get('module_handler')->moduleExists('comment'), 'Comment module enabled.');

    // Create nodes of each type.
    $this->container->get('comment.manager')->addDefaultField('node', 'book');
    $book_node = $this->drupalCreateNode(array('type' => 'book'));

    $this->drupalLogout();

    // Try to post a comment on each node. A failure will be triggered if the
    // comment body is missing on one of these forms, due to postComment()
    // asserting that the body is actually posted correctly.
    $this->web_user = $this->drupalCreateUser(array('access content', 'access comments', 'post comments', 'skip comment approval'));
    $this->drupalLogin($this->web_user);
    $this->postComment($book_node, $this->randomMachineName(), $this->randomMachineName());
  }

  /**
   * Tests that comment module works correctly with plain text format.
   */
  function testCommentFormat() {
    // Disable text processing for comments.
    $this->drupalLogin($this->admin_user);
    $edit = array('instance[settings][text_processing]' => 0);
    $this->drupalPostForm('admin/structure/comment/manage/comment/fields/comment.comment.comment_body', $edit, t('Save settings'));

    // Change formatter settings.
    $this->drupalGet('admin/structure/comment/manage/comment/display');
    $edit = array('fields[comment_body][type]' => 'text_trimmed', 'refresh_rows' => 'comment_body');
    $commands = $this->drupalPostAjaxForm(NULL, $edit, array('op' => t('Refresh')));
    $this->assertTrue($commands, 'Ajax commands returned');

    // Post a comment without an explicit subject.
    $this->drupalLogin($this->web_user);
    $edit = array('comment_body[0][value]' => $this->randomMachineName(8));
    $this->drupalPostForm('node/' . $this->node->id(), $edit, t('Save'));
  }
}
