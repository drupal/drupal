<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d6;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade comments.
 *
 * @group migrate_drupal_6
 */
class MigrateCommentTest extends MigrateDrupal6TestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['comment'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installConfig(['comment']);

    // The entity.node.canonical route must exist when the RDF hook is called.
    $this->container->get('router.builder')->rebuild();

    $this->migrateContent();
    $this->executeMigrations([
      'd6_node',
      'd6_comment_type',
      'd6_comment_field',
      'd6_comment_field_instance',
      'd6_comment_entity_display',
      'd6_comment_entity_form_display',
      'd6_comment',
    ]);
  }

  /**
   * Tests the Drupal 6 to Drupal 8 comment migration.
   */
  public function testComments() {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $comment_storage */
    $comment_storage = $this->container->get('entity.manager')->getStorage('comment');
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $comment_storage->load(1);
    $this->assertIdentical('The first comment.', $comment->getSubject());
    $this->assertIdentical('The first comment body.', $comment->comment_body->value);
    $this->assertIdentical('filtered_html', $comment->comment_body->format);
    $this->assertIdentical('0', $comment->pid->target_id);
    $this->assertIdentical('1', $comment->getCommentedEntityId());
    $this->assertIdentical('node', $comment->getCommentedEntityTypeId());
    $this->assertIdentical('en', $comment->language()->getId());
    $this->assertIdentical('comment_no_subject', $comment->getTypeId());

    $comment = $comment_storage->load(2);
    $this->assertIdentical('The response to the second comment.', $comment->subject->value);
    $this->assertIdentical('3', $comment->pid->target_id);

    $comment = $comment_storage->load(3);
    $this->assertIdentical('The second comment.', $comment->subject->value);
    $this->assertIdentical('0', $comment->pid->target_id);
  }
}
