<?php

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests comment validation constraints.
 *
 * @group comment
 */
class CommentValidationTest extends EntityKernelTestBase {
  use CommentTestTrait;
  use EntityReferenceFieldCreationTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['comment', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installConfig(['comment']);
  }

  /**
   * Tests the comment validation constraints.
   */
  public function testValidation() {
    // Add a user.
    $user = User::create(['name' => 'test', 'status' => TRUE]);
    $user->save();

    // Add comment type.
    $this->entityTypeManager->getStorage('comment_type')->create([
      'id' => 'comment',
      'label' => 'comment',
      'target_entity_type_id' => 'node',
    ])->save();

    // Add comment field to content.
    $this->entityTypeManager->getStorage('field_storage_config')->create([
      'entity_type' => 'node',
      'field_name' => 'comment',
      'type' => 'comment',
      'settings' => [
        'comment_type' => 'comment',
      ],
    ])->save();

    // Create a page node type.
    $this->entityTypeManager->getStorage('node_type')->create([
      'type' => 'page',
      'name' => 'page',
    ])->save();

    // Add comment field to page content.
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = $this->entityTypeManager->getStorage('field_config')->create([
      'field_name' => 'comment',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Comment settings',
    ]);
    $field->save();

    $node = $this->entityTypeManager->getStorage('node')->create([
      'type' => 'page',
      'title' => 'test',
    ]);
    $node->save();

    $comment = $this->entityTypeManager->getStorage('comment')->create([
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'comment_body' => $this->randomMachineName(),
    ]);

    $violations = $comment->validate();
    $this->assertCount(0, $violations, 'No violations when validating a default comment.');

    $comment->set('subject', $this->randomString(65));
    $this->assertLengthViolation($comment, 'subject', 64);

    // Make the subject valid.
    $comment->set('subject', $this->randomString());
    $comment->set('name', $this->randomString(61));
    $this->assertLengthViolation($comment, 'name', 60);

    // Validate a name collision between an anonymous comment author name and an
    // existing user account name.
    $comment->set('name', 'test');
    $comment->set('uid', 0);
    $violations = $comment->validate();
    $this->assertCount(1, $violations, "Violation found on author name collision");
    $this->assertEquals("name", $violations[0]->getPropertyPath());
    $this->assertEquals('The name you used (test) belongs to a registered user.', $violations[0]->getMessage());

    // Make the name valid.
    $comment->set('name', 'valid unused name');
    $comment->set('mail', 'invalid');
    $violations = $comment->validate();
    $this->assertCount(1, $violations, 'Violation found when email is invalid');
    $this->assertEquals('mail.0.value', $violations[0]->getPropertyPath());
    $this->assertEquals('This value is not a valid email address.', $violations[0]->getMessage());

    $comment->set('mail', NULL);
    $comment->set('homepage', 'http://example.com/' . $this->randomMachineName(237));
    $this->assertLengthViolation($comment, 'homepage', 255);

    $comment->set('homepage', 'invalid');
    $violations = $comment->validate();
    $this->assertCount(1, $violations, 'Violation found when homepage is invalid');
    $this->assertEquals('homepage.0.value', $violations[0]->getPropertyPath());

    // @todo This message should be improved in
    //   https://www.drupal.org/node/2012690.
    $this->assertEquals('This value should be of the correct primitive type.', $violations[0]->getMessage());

    $comment->set('homepage', NULL);
    $comment->set('hostname', $this->randomString(129));
    $this->assertLengthViolation($comment, 'hostname', 128);

    $comment->set('hostname', NULL);
    $comment->set('thread', $this->randomString(256));
    $this->assertLengthViolation($comment, 'thread', 255);

    $comment->set('thread', NULL);

    // Force anonymous users to enter contact details.
    $field->setSetting('anonymous', CommentInterface::ANONYMOUS_MUST_CONTACT);
    $field->save();
    // Reset the node entity.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node->id()]);
    $node = Node::load($node->id());
    // Create a new comment with the new field.
    $comment = $this->entityTypeManager->getStorage('comment')->create([
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'comment_body' => $this->randomMachineName(),
      'uid' => 0,
      'name' => '',
    ]);
    $violations = $comment->validate();
    $this->assertCount(1, $violations, 'Violation found when name is required, but empty and UID is anonymous.');
    $this->assertEquals('name', $violations[0]->getPropertyPath());
    $this->assertEquals('You have to specify a valid author.', $violations[0]->getMessage());

    // Test creating a default comment with a given user id works.
    $comment = $this->entityTypeManager->getStorage('comment')->create([
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'comment_body' => $this->randomMachineName(),
      'uid' => $user->id(),
    ]);
    $violations = $comment->validate();
    $this->assertCount(0, $violations, 'No violations when validating a default comment with an author.');

    // Test specifying a wrong author name does not work.
    $comment = $this->entityTypeManager->getStorage('comment')->create([
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'comment_body' => $this->randomMachineName(),
      'uid' => $user->id(),
      'name' => 'not-test',
    ]);
    $violations = $comment->validate();
    $this->assertCount(1, $violations, 'Violation found when author name and comment author do not match.');
    $this->assertEquals('name', $violations[0]->getPropertyPath());
    $this->assertEquals('The specified author name does not match the comment author.', $violations[0]->getMessage());
  }

  /**
   * Tests that comments of unpublished nodes are not valid.
   */
  public function testValidationOfCommentOfUnpublishedNode() {
    // Create a page node type.
    $this->entityTypeManager->getStorage('node_type')->create([
      'type' => 'page',
      'name' => 'page',
    ])->save();

    // Create a comment type.
    CommentType::create([
      'id' => 'comment',
      'label' => 'Default comments',
      'description' => 'Default comment field',
      'target_entity_type_id' => 'node',
    ])->save();

    // Add comment and entity reference comment fields.
    $this->addDefaultCommentField('node', 'page', 'comment');
    $this->createEntityReferenceField(
      'node',
      'page',
      'entity_reference_comment',
      'Entity Reference Comment',
      'comment',
      'default',
      ['target_bundles' => ['comment']]
    );

    $comment_admin_user = $this->drupalCreateUser([
      'skip comment approval',
      'post comments',
      'access comments',
      'access content',
      'administer nodes',
      'administer comments',
      'bypass node access',
    ]);
    $comment_non_admin_user = $this->drupalCreateUser([
      'access comments',
      'post comments',
      'create page content',
      'edit own comments',
      'skip comment approval',
      'access content',
    ]);

    // Create a node with a comment and make it unpublished.
    $node1 = $this->entityTypeManager->getStorage('node')->create([
      'type' => 'page',
      'title' => 'test 1',
      'promote' => 1,
      'status' => 0,
      'uid' => $comment_non_admin_user->id(),
    ]);
    $node1->save();
    $comment1 = $this->entityTypeManager->getStorage('comment')->create([
      'entity_id' => $node1->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'comment_body' => $this->randomMachineName(),
    ]);
    $comment1->save();
    $this->assertInstanceOf(Comment::class, $comment1);

    // Create a second published node.
    /** @var \Drupal\node\Entity\Node $node2 */
    $node2 = $this->entityTypeManager->getStorage('node')->create([
      'type' => 'page',
      'title' => 'test 2',
      'promote' => 1,
      'status' => 1,
      'uid' => $comment_non_admin_user->id(),
    ]);
    $node2->save();

    // Test the validation API directly.
    $this->drupalSetCurrentUser($comment_non_admin_user);
    $this->assertEquals(\Drupal::currentUser()->id(), $comment_non_admin_user->id());
    $node2->set('entity_reference_comment', $comment1->id());
    $violations = $node2->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('entity_reference_comment.0.target_id', $violations[0]->getPropertyPath());
    $this->assertEquals(sprintf('This entity (%s: %s) cannot be referenced.', $comment1->getEntityTypeId(), $comment1->id()), $violations[0]->getMessage());

    $this->drupalSetCurrentUser($comment_admin_user);
    $this->assertEquals(\Drupal::currentUser()->id(), $comment_admin_user->id());
    $node2->set('entity_reference_comment', $comment1->id());
    $violations = $node2->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Verifies that a length violation exists for the given field.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment object to validate.
   * @param string $field_name
   *   The field that violates the maximum length.
   * @param int $length
   *   Number of characters that was exceeded.
   *
   * @internal
   */
  protected function assertLengthViolation(CommentInterface $comment, string $field_name, int $length): void {
    $violations = $comment->validate();
    $this->assertCount(1, $violations, "Violation found when $field_name is too long.");
    $this->assertEquals("{$field_name}.0.value", $violations[0]->getPropertyPath());
    $field_label = $comment->get($field_name)->getFieldDefinition()->getLabel();
    $this->assertEquals("{$field_label}: may not be longer than {$length} characters.", $violations[0]->getMessage());
  }

}
