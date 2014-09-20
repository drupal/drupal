<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\CommentTypeTest.
 */

namespace Drupal\comment\Tests;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;

/**
 * Ensures that comment type functions work correctly.
 *
 * @group comment
 */
class CommentTypeTest extends CommentTestBase {

  /**
   * Admin user
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = array(
    'administer comments',
    'administer comment fields',
    'administer comment types',
  );

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser($this->permissions);
  }

  /**
   * Tests creating a comment type programmatically and via a form.
   */
  public function testCommentTypeCreation() {
    // Create a comment type programmatically.
    $type = $this->createCommentType('other');

    $comment_type = CommentType::load('other');
    $this->assertTrue($comment_type, 'The new comment type has been created.');

    // Login a test user.
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/structure/comment/manage/' . $type->id());
    $this->assertResponse(200, 'The new comment type can be accessed at the edit form.');

    // Create a comment type via the user interface.
    $edit = array(
      'id' => 'foo',
      'label' => 'title for foo',
      'description' => '',
      'target_entity_type_id' => 'node',
    );
    $this->drupalPostForm('admin/structure/comment/types/add', $edit, t('Save'));
    $comment_type = CommentType::load('foo');
    $this->assertTrue($comment_type, 'The new comment type has been created.');

    // Check that the comment type was created in site default language.
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->id;
    $this->assertEqual($comment_type->language()->getId(), $default_langcode);

    // Edit the comment-type and ensure that we cannot change the entity-type.
    $this->drupalGet('admin/structure/comment/manage/foo');
    $this->assertNoField('target_entity_type_id', 'Entity type file not present');
    $this->assertText(t('Target entity type'));
    // Save the form and ensure the entity-type value is preserved even though
    // the field isn't present.
    $this->drupalPostForm(NULL, array(), t('Save'));
    \Drupal::entityManager()->getStorage('comment_type')->resetCache(array('foo'));
    $comment_type = CommentType::load('foo');
    $this->assertEqual($comment_type->getTargetEntityTypeId(), 'node');
  }

  /**
   * Tests editing a comment type using the UI.
   */
  public function testCommentTypeEditing() {
    $this->drupalLogin($this->adminUser);

    $field = FieldConfig::loadByName('comment', 'comment', 'comment_body');
    $this->assertEqual($field->getLabel(), 'Comment', 'Comment body field was found.');

    // Change the comment type name.
    $this->drupalGet('admin/structure/comment');
    $edit = array(
      'label' => 'Bar',
    );
    $this->drupalPostForm('admin/structure/comment/manage/comment', $edit, t('Save'));

    $this->drupalGet('admin/structure/comment');
    $this->assertRaw('Bar', 'New name was displayed.');
    $this->clickLink('Manage fields');
    $this->assertEqual(url('admin/structure/comment/manage/comment/fields', array('absolute' => TRUE)), $this->getUrl(), 'Original machine name was used in URL.');

    // Remove the body field.
    $this->drupalPostForm('admin/structure/comment/manage/comment/fields/comment.comment.comment_body/delete', array(), t('Delete'));
    // Resave the settings for this type.
    $this->drupalPostForm('admin/structure/comment/manage/comment', array(), t('Save'));
    // Check that the body field doesn't exist.
    $this->drupalGet('admin/structure/comment/manage/comment/fields');
    $this->assertNoRaw('comment_body', 'Body field was not found.');
  }

  /**
   * Tests deleting a comment type that still has content.
   */
  public function testCommentTypeDeletion() {
    // Create a comment type programmatically.
    $type = $this->createCommentType('foo');
    $this->drupalCreateContentType(array('type' => 'page'));
    \Drupal::service('comment.manager')->addDefaultField('node', 'page', 'foo', CommentItemInterface::OPEN, 'foo');
    $field_storage = FieldStorageConfig::loadByName('node', 'foo');

    $this->drupalLogin($this->adminUser);

    // Create a node.
    $node = Node::create(array(
      'type' => 'page',
      'title' => 'foo',
    ));
    $node->save();

    // Add a new comment of this type.
    $comment = Comment::create(array(
      'comment_type' => 'foo',
      'entity_type' => 'node',
      'field_name' => 'foo',
      'entity_id' => $node->id(),
    ));
    $comment->save();

    // Attempt to delete the comment type, which should not be allowed.
    $this->drupalGet('admin/structure/comment/manage/' . $type->id() . '/delete');
    $this->assertRaw(
      t('%label is used by 1 comment on your site. You can not remove this comment type until you have removed all of the %label comments.', array('%label' => $type->label())),
      'The comment type will not be deleted until all comments of that type are removed.'
    );
    $this->assertRaw(
      t('%label is used by the %field field on your site. You can not remove this comment type until you have removed the field.', array(
        '%label' => 'foo',
        '%field' => 'node.foo',
      )),
      'The comment type will not be deleted until all fields of that type are removed.'
    );
    $this->assertNoText(t('This action cannot be undone.'), 'The comment type deletion confirmation form is not available.');

    // Delete the comment and the field.
    $comment->delete();
    $field_storage->delete();
    // Attempt to delete the comment type, which should now be allowed.
    $this->drupalGet('admin/structure/comment/manage/' . $type->id() . '/delete');
    $this->assertRaw(
      t('Are you sure you want to delete %type?', array('%type' => $type->id())),
      'The comment type is available for deletion.'
    );
    $this->assertText(t('This action cannot be undone.'), 'The comment type deletion confirmation form is available.');

    // Test exception thrown when re-using an existing comment type.
    try {
      \Drupal::service('comment.manager')->addDefaultField('comment', 'comment', 'bar');
      $this->fail('Exception not thrown.');
    }
    catch (\InvalidArgumentException $e) {
      $this->pass('Exception thrown if attempting to re-use comment-type from another entity type.');
    }

    // Delete the comment type.
    $this->drupalPostForm('admin/structure/comment/manage/' . $type->id() . '/delete', array(), t('Delete'));
    $this->assertNull(CommentType::load($type->id()), 'Comment type deleted.');
    $this->assertRaw(t('Comment type %label has been deleted.', array('%label' => $type->label())));
  }

}
