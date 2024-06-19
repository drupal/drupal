<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Functional;

use Drupal\Core\Url;
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
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('system_breadcrumb_block');

    $this->adminUser = $this->drupalCreateUser($this->permissions);
  }

  /**
   * Tests creating a comment type programmatically and via a form.
   */
  public function testCommentTypeCreation(): void {
    // Create a comment type programmatically.
    $type = $this->createCommentType('other');

    $comment_type = CommentType::load('other');
    $this->assertInstanceOf(CommentType::class, $comment_type);

    // Log in a test user.
    $this->drupalLogin($this->adminUser);

    // Ensure that the new comment type admin page can be accessed.
    $this->drupalGet('admin/structure/comment/manage/' . $type->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementTextEquals('css', 'h1', "Edit {$comment_type->label()} comment type");

    // Create a comment type via the user interface.
    $edit = [
      'id' => 'foo',
      'label' => 'title for foo',
      'description' => '',
    ];
    $this->drupalGet('admin/structure/comment/types/add');

    // Ensure that target entity type is a required field.
    $this->submitForm($edit, 'Save and manage fields');
    $this->assertSession()->pageTextContains('Target entity type field is required.');

    // Ensure that comment type is saved when target entity type is provided.
    $edit['target_entity_type_id'] = 'node';
    $this->submitForm($edit, 'Save and manage fields');
    $this->assertSession()->pageTextContains('Comment type title for foo has been added.');

    // Asserts that form submit redirects to the expected manage fields page.
    $this->assertSession()->addressEquals('admin/structure/comment/manage/' . $edit['id'] . '/fields');

    // Asserts that the comment type is visible in breadcrumb.
    $this->assertTrue($this->assertSession()->elementExists('css', 'nav[role="navigation"]')->hasLink('title for foo'));

    $comment_type = CommentType::load('foo');
    $this->assertInstanceOf(CommentType::class, $comment_type);

    // Check that the comment type was created in site default language.
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $this->assertEquals($default_langcode, $comment_type->language()->getId());

    // Edit the comment-type and ensure that we cannot change the entity-type.
    $this->drupalGet('admin/structure/comment/manage/foo');
    $this->assertSession()->fieldNotExists('target_entity_type_id');
    $this->assertSession()->pageTextContains('Target entity type');
    // Save the form and ensure the entity-type value is preserved even though
    // the field isn't present.
    $this->submitForm([], 'Save');
    \Drupal::entityTypeManager()->getStorage('comment_type')->resetCache(['foo']);
    $comment_type = CommentType::load('foo');
    $this->assertEquals('node', $comment_type->getTargetEntityTypeId());

    // Ensure that target type is displayed in the comment type list.
    $this->drupalGet('admin/structure/comment');
    $this->assertSession()->elementExists('xpath', '//td[text() = "Content"]');
  }

  /**
   * Tests editing a comment type using the UI.
   */
  public function testCommentTypeEditing(): void {
    $this->drupalLogin($this->adminUser);

    $field = FieldConfig::loadByName('comment', 'comment', 'comment_body');
    $this->assertEquals('Comment', $field->getLabel(), 'Comment body field was found.');

    // Change the comment type name.
    $this->drupalGet('admin/structure/comment');
    $edit = [
      'label' => 'Bar',
    ];
    $this->drupalGet('admin/structure/comment/manage/comment');
    $this->submitForm($edit, 'Save');

    $this->drupalGet('admin/structure/comment');
    $this->assertSession()->pageTextContains('Bar');
    $this->clickLink('Manage fields');
    // Verify that the original machine name was used in the URL.
    $this->assertSession()->addressEquals(Url::fromRoute('entity.comment.field_ui_fields', ['comment_type' => 'comment']));
    $this->assertCount(1, $this->cssSelect('tr#comment-body'), 'Body field exists.');

    // Remove the body field.
    $this->drupalGet('admin/structure/comment/manage/comment/fields/comment.comment.comment_body/delete');
    $this->submitForm([], 'Delete');
    // Resave the settings for this type.
    $this->drupalGet('admin/structure/comment/manage/comment');
    $this->submitForm([], 'Save');
    // Check that the body field doesn't exist.
    $this->drupalGet('admin/structure/comment/manage/comment/fields');
    $this->assertCount(0, $this->cssSelect('tr#comment-body'), 'Body field does not exist.');
  }

  /**
   * Tests deleting a comment type that still has content.
   */
  public function testCommentTypeDeletion(): void {
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
    $this->assertSession()->pageTextContains($type->label() . ' is used by 1 comment on your site. You can not remove this comment type until you have removed all of the ' . $type->label() . ' comments.');
    $this->assertSession()->pageTextContains('foo is used by the node.foo field on your site. You can not remove this comment type until you have removed the field.');
    $this->assertSession()->pageTextNotContains('This action cannot be undone.');

    // Delete the comment and the field.
    $comment->delete();
    $field_storage->delete();
    // Attempt to delete the comment type, which should now be allowed.
    $this->drupalGet('admin/structure/comment/manage/' . $type->id() . '/delete');
    $this->assertSession()->pageTextContains('Are you sure you want to delete the comment type ' . $type->id() . '?');
    $this->assertSession()->pageTextContains('This action cannot be undone.');

    // Test exception thrown when re-using an existing comment type.
    try {
      $this->addDefaultCommentField('comment', 'comment', 'bar');
      $this->fail('Exception not thrown.');
    }
    catch (\InvalidArgumentException $e) {
      // Expected exception; just continue testing.
    }

    // Delete the comment type.
    $this->drupalGet('admin/structure/comment/manage/' . $type->id() . '/delete');
    $this->submitForm([], 'Delete');
    $this->assertNull(CommentType::load($type->id()), 'Comment type deleted.');
    $this->assertSession()->pageTextContains('The comment type ' . $type->label() . ' has been deleted.');
  }

}
