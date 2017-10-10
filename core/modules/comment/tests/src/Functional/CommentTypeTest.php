<?php

namespace Drupal\Tests\comment\Functional;

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
   * Admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer comments',
    'administer comment fields',
    'administer comment types',
  ];

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('page_title_block');

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

    // Log in a test user.
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/structure/comment/manage/' . $type->id());
    $this->assertResponse(200, 'The new comment type can be accessed at the edit form.');

    // Create a comment type via the user interface.
    $edit = [
      'id' => 'foo',
      'label' => 'title for foo',
      'description' => '',
      'target_entity_type_id' => 'node',
    ];
    $this->drupalPostForm('admin/structure/comment/types/add', $edit, t('Save'));
    $comment_type = CommentType::load('foo');
    $this->assertTrue($comment_type, 'The new comment type has been created.');

    // Check that the comment type was created in site default language.
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $this->assertEqual($comment_type->language()->getId(), $default_langcode);

    // Edit the comment-type and ensure that we cannot change the entity-type.
    $this->drupalGet('admin/structure/comment/manage/foo');
    $this->assertNoField('target_entity_type_id', 'Entity type file not present');
    $this->assertText(t('Target entity type'));
    // Save the form and ensure the entity-type value is preserved even though
    // the field isn't present.
    $this->drupalPostForm(NULL, [], t('Save'));
    \Drupal::entityManager()->getStorage('comment_type')->resetCache(['foo']);
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
    $edit = [
      'label' => 'Bar',
    ];
    $this->drupalPostForm('admin/structure/comment/manage/comment', $edit, t('Save'));

    $this->drupalGet('admin/structure/comment');
    $this->assertRaw('Bar', 'New name was displayed.');
    $this->clickLink('Manage fields');
    $this->assertUrl(\Drupal::url('entity.comment.field_ui_fields', ['comment_type' => 'comment'], ['absolute' => TRUE]), [], 'Original machine name was used in URL.');
    $this->assertTrue($this->cssSelect('tr#comment-body'), 'Body field exists.');

    // Remove the body field.
    $this->drupalPostForm('admin/structure/comment/manage/comment/fields/comment.comment.comment_body/delete', [], t('Delete'));
    // Resave the settings for this type.
    $this->drupalPostForm('admin/structure/comment/manage/comment', [], t('Save'));
    // Check that the body field doesn't exist.
    $this->drupalGet('admin/structure/comment/manage/comment/fields');
    $this->assertFalse($this->cssSelect('tr#comment-body'), 'Body field does not exist.');
  }

  /**
   * Tests deleting a comment type that still has content.
   */
  public function testCommentTypeDeletion() {
    // Create a comment type programmatically.
    $type = $this->createCommentType('foo');
    $this->drupalCreateContentType(['type' => 'page']);
    $this->addDefaultCommentField('node', 'page', 'foo', CommentItemInterface::OPEN, 'foo');
    $field_storage = FieldStorageConfig::loadByName('node', 'foo');

    $this->drupalLogin($this->adminUser);

    // Create a node.
    $node = Node::create([
      'type' => 'page',
      'title' => 'foo',
    ]);
    $node->save();

    // Add a new comment of this type.
    $comment = Comment::create([
      'comment_type' => 'foo',
      'entity_type' => 'node',
      'field_name' => 'foo',
      'entity_id' => $node->id(),
    ]);
    $comment->save();

    // Attempt to delete the comment type, which should not be allowed.
    $this->drupalGet('admin/structure/comment/manage/' . $type->id() . '/delete');
    $this->assertRaw(
      t('%label is used by 1 comment on your site. You can not remove this comment type until you have removed all of the %label comments.', ['%label' => $type->label()]),
      'The comment type will not be deleted until all comments of that type are removed.'
    );
    $this->assertRaw(
      t('%label is used by the %field field on your site. You can not remove this comment type until you have removed the field.', [
        '%label' => 'foo',
        '%field' => 'node.foo',
      ]),
      'The comment type will not be deleted until all fields of that type are removed.'
    );
    $this->assertNoText(t('This action cannot be undone.'), 'The comment type deletion confirmation form is not available.');

    // Delete the comment and the field.
    $comment->delete();
    $field_storage->delete();
    // Attempt to delete the comment type, which should now be allowed.
    $this->drupalGet('admin/structure/comment/manage/' . $type->id() . '/delete');
    $this->assertRaw(
      t('Are you sure you want to delete the comment type %type?', ['%type' => $type->id()]),
      'The comment type is available for deletion.'
    );
    $this->assertText(t('This action cannot be undone.'), 'The comment type deletion confirmation form is available.');

    // Test exception thrown when re-using an existing comment type.
    try {
      $this->addDefaultCommentField('comment', 'comment', 'bar');
      $this->fail('Exception not thrown.');
    }
    catch (\InvalidArgumentException $e) {
      $this->pass('Exception thrown if attempting to re-use comment-type from another entity type.');
    }

    // Delete the comment type.
    $this->drupalPostForm('admin/structure/comment/manage/' . $type->id() . '/delete', [], t('Delete'));
    $this->assertNull(CommentType::load($type->id()), 'Comment type deleted.');
    $this->assertRaw(t('The comment type %label has been deleted.', ['%label' => $type->label()]));
  }

}
