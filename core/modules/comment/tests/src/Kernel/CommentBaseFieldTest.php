<?php

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment_base_field_test\Entity\CommentTestBaseField;
use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that comment as a base field.
 *
 * @group comment
 */
class CommentBaseFieldTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'comment',
    'comment_base_field_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('comment_test_base_field');
    $this->installEntitySchema('comment');
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
  }

  /**
   * Tests comment as a base field.
   */
  public function testCommentBaseField() {
    // Verify entity creation.
    $entity = CommentTestBaseField::create([
      'name' => $this->randomMachineName(),
      'test_comment' => CommentItemInterface::OPEN,
    ]);
    $entity->save();

    $comment = Comment::create([
      'entity_id' => $entity->id(),
      'entity_type' => 'comment_test_base_field',
      'field_name' => 'test_comment',
      'pid' => 0,
      'uid' => 0,
      'status' => CommentInterface::PUBLISHED,
      'subject' => $this->randomMachineName(),
      'hostname' => '127.0.0.1',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'comment_body' => [['value' => $this->randomMachineName()]],
    ]);
    $comment->save();
    $this->assertEquals('test_comment_type', $comment->bundle());
  }

}
