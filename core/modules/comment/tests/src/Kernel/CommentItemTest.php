<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests the new entity API for the comment field type.
 *
 * @group comment
 */
class CommentItemTest extends FieldKernelTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment', 'entity_test', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('comment');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installConfig(['comment']);
  }

  /**
   * Tests using entity fields of the comment field type.
   */
  public function testCommentItem(): void {
    $this->addDefaultCommentField('entity_test', 'entity_test', 'comment');

    // Verify entity creation.
    $entity = EntityTest::create();
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $storage = $this->container->get('entity_type.manager')->getStorage('entity_test');
    $storage->resetCache([$id]);
    $entity = $storage->load($id);
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->comment);
    $this->assertInstanceOf(CommentItemInterface::class, $entity->comment[0]);

    // Test sample item generation.
    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = EntityTest::create();
    $entity->comment->generateSampleItems();
    $this->entityValidateAndSave($entity);
    $this->assertContains($entity->get('comment')->status, [
      CommentItemInterface::HIDDEN,
      CommentItemInterface::CLOSED,
      CommentItemInterface::OPEN,
    ], 'Comment status value in defined range');

    $mainProperty = $entity->comment[0]->mainPropertyName();
    $this->assertEquals('status', $mainProperty);
  }

  /**
   * Tests comment author name.
   */
  public function testCommentAuthorName(): void {
    $this->installEntitySchema('comment');
    $this->addDefaultCommentField('entity_test', 'entity_test', 'comment');

    $host = EntityTest::create(['name' => $this->randomString()]);
    $host->save();

    // Create some comments.
    $comment = Comment::create([
      'subject' => 'My comment title',
      'uid' => 1,
      'name' => 'entity-test',
      'mail' => 'entity@localhost',
      'entity_type' => 'entity_test',
      'field_name' => 'comment',
      'entity_id' => $host->id(),
      'comment_type' => 'entity_test',
      'status' => 1,
    ]);
    $comment->save();

    // The entity fields for name and mail have no meaning if the user is not
    // Anonymous.
    $this->assertNull($comment->name->value);
    $this->assertNull($comment->mail->value);

    $comment_anonymous = Comment::create([
      'subject' => 'Anonymous comment title',
      'uid' => 0,
      'name' => 'barry',
      'mail' => 'test@example.com',
      'homepage' => 'https://example.com',
      'entity_type' => 'entity_test',
      'field_name' => 'comment',
      'entity_id' => $host->id(),
      'comment_type' => 'entity_test',
      'status' => 1,
    ]);
    $comment_anonymous->save();

    // The entity fields for name and mail have retained their values when
    // comment belongs to an anonymous user.
    $this->assertNotNull($comment_anonymous->name->value);
    $this->assertNotNull($comment_anonymous->mail->value);

    $comment_anonymous->setOwnerId(1)
      ->save();
    // The entity fields for name and mail have no meaning if the user is not
    // Anonymous.
    $this->assertNull($comment_anonymous->name->value);
    $this->assertNull($comment_anonymous->mail->value);
  }

}
