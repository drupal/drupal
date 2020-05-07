<?php

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\CommentInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Tests comment validation constraints.
 *
 * @group comment
 */
class CommentValidationTest extends EntityKernelTestBase {

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
    $this->assertEqual($violations[0]->getPropertyPath(), "name");
    $this->assertEqual($violations[0]->getMessage(), t('The name you used (%name) belongs to a registered user.', ['%name' => 'test']));

    // Make the name valid.
    $comment->set('name', 'valid unused name');
    $comment->set('mail', 'invalid');
    $violations = $comment->validate();
    $this->assertCount(1, $violations, 'Violation found when email is invalid');
    $this->assertEqual($violations[0]->getPropertyPath(), 'mail.0.value');
    $this->assertEqual($violations[0]->getMessage(), t('This value is not a valid email address.'));

    $comment->set('mail', NULL);
    $comment->set('homepage', 'http://example.com/' . $this->randomMachineName(237));
    $this->assertLengthViolation($comment, 'homepage', 255);

    $comment->set('homepage', 'invalid');
    $violations = $comment->validate();
    $this->assertCount(1, $violations, 'Violation found when homepage is invalid');
    $this->assertEqual($violations[0]->getPropertyPath(), 'homepage.0.value');

    // @todo This message should be improved in
    //   https://www.drupal.org/node/2012690.
    $this->assertEqual($violations[0]->getMessage(), t('This value should be of the correct primitive type.'));

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
    $this->assertEqual($violations[0]->getPropertyPath(), 'name');
    $this->assertEqual($violations[0]->getMessage(), t('You have to specify a valid author.'));

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
    $this->assertEqual($violations[0]->getPropertyPath(), 'name');
    $this->assertEqual($violations[0]->getMessage(), t('The specified author name does not match the comment author.'));
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
   */
  protected function assertLengthViolation(CommentInterface $comment, $field_name, $length) {
    $violations = $comment->validate();
    $this->assertCount(1, $violations, "Violation found when $field_name is too long.");
    $this->assertEqual($violations[0]->getPropertyPath(), "$field_name.0.value");
    $field_label = $comment->get($field_name)->getFieldDefinition()->getLabel();
    $this->assertEqual($violations[0]->getMessage(), t('%name: may not be longer than @max characters.', ['%name' => $field_label, '@max' => $length]));
  }

}
