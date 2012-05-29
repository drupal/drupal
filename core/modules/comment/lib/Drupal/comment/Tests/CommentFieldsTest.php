<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentFieldsTest.
 */

namespace Drupal\comment\Tests;

/**
 * Tests fields on comments.
 */
class CommentFieldsTest extends CommentTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Comment fields',
      'description' => 'Tests fields on comments.',
      'group' => 'Comment',
    );
  }

  /**
   * Tests that the default 'comment_body' field is correctly added.
   */
  function testCommentDefaultFields() {
    // Do not make assumptions on default node types created by the test
    // install profile, and create our own.
    $this->drupalCreateContentType(array('type' => 'test_node_type'));

    // Check that the 'comment_body' field is present on all comment bundles.
    $instances = field_info_instances('comment');
    foreach (node_type_get_types() as $type_name => $info) {
      $this->assertTrue(isset($instances['comment_node_' . $type_name]['comment_body']), t('The comment_body field is present for comments on type @type', array('@type' => $type_name)));

      // Delete the instance along the way.
      field_delete_instance($instances['comment_node_' . $type_name]['comment_body']);
    }

    // Check that the 'comment_body' field is deleted.
    $field = field_info_field('comment_body');
    $this->assertTrue(empty($field), t('The comment_body field was deleted'));

    // Create a new content type.
    $type_name = 'test_node_type_2';
    $this->drupalCreateContentType(array('type' => $type_name));

    // Check that the 'comment_body' field exists and has an instance on the
    // new comment bundle.
    $field = field_info_field('comment_body');
    $this->assertTrue($field, t('The comment_body field exists'));
    $instances = field_info_instances('comment');
    $this->assertTrue(isset($instances['comment_node_' . $type_name]['comment_body']), t('The comment_body field is present for comments on type @type', array('@type' => $type_name)));
  }

  /**
   * Tests that comment module works when enabled after a content module.
   */
  function testCommentEnable() {
    // Create a user to do module administration.
    $this->admin_user = $this->drupalCreateUser(array('access administration pages', 'administer modules'));
    $this->drupalLogin($this->admin_user);

    // Disable the comment module.
    $edit = array();
    $edit['modules[Core][comment][enable]'] = FALSE;
    $this->drupalPost('admin/modules', $edit, t('Save configuration'));
    $this->resetAll();
    $this->assertFalse(module_exists('comment'), t('Comment module disabled.'));

    // Enable core content type modules (book, and poll).
    $edit = array();
    $edit['modules[Core][book][enable]'] = 'book';
    $edit['modules[Core][poll][enable]'] = 'poll';
    $this->drupalPost('admin/modules', $edit, t('Save configuration'));

    // Now enable the comment module.
    $edit = array();
    $edit['modules[Core][comment][enable]'] = 'comment';
    $this->drupalPost('admin/modules', $edit, t('Save configuration'));
    $this->resetAll();
    $this->assertTrue(module_exists('comment'), t('Comment module enabled.'));

    // Create nodes of each type.
    $book_node = $this->drupalCreateNode(array('type' => 'book'));
    $poll_node = $this->drupalCreateNode(array('type' => 'poll', 'active' => 1, 'runtime' => 0, 'choice' => array(array('chtext' => ''))));

    $this->drupalLogout();

    // Try to post a comment on each node. A failure will be triggered if the
    // comment body is missing on one of these forms, due to postComment()
    // asserting that the body is actually posted correctly.
    $this->web_user = $this->drupalCreateUser(array('access content', 'access comments', 'post comments', 'skip comment approval'));
    $this->drupalLogin($this->web_user);
    $this->postComment($book_node, $this->randomName(), $this->randomName());
    $this->postComment($poll_node, $this->randomName(), $this->randomName());
  }

  /**
   * Tests that comment module works correctly with plain text format.
   */
  function testCommentFormat() {
    // Disable text processing for comments.
    $this->drupalLogin($this->admin_user);
    $edit = array('instance[settings][text_processing]' => 0);
    $this->drupalPost('admin/structure/types/manage/article/comment/fields/comment_body', $edit, t('Save settings'));

    // Post a comment without an explicit subject.
    $this->drupalLogin($this->web_user);
    $edit = array('comment_body[und][0][value]' => $this->randomName(8));
    $this->drupalPost('node/' . $this->node->nid, $edit, t('Save'));
  }
}
