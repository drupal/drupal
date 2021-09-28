<?php

namespace Drupal\Tests\comment\Kernel;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\EntityViewTrait;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests loading and rendering orphan comments.
 *
 * @group comment
 */
class CommentOrphanTest extends EntityKernelTestBase {

  use EntityViewTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment', 'node', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['user']);
    $this->installEntitySchema('date_format');
    $this->installEntitySchema('comment');
    $this->installSchema('comment', ['comment_entity_statistics']);
  }

  /**
   * Test loading/deleting/rendering orphaned comments.
   *
   * @dataProvider providerTestOrphan
   */
  public function testOrphan($property) {

    DateFormat::create([
      'id' => 'fallback',
      'label' => 'Fallback',
      'pattern' => 'Y-m-d',
    ])->save();

    $comment_storage = $this->entityTypeManager->getStorage('comment');
    $node_storage = $this->entityTypeManager->getStorage('node');

    // Create a page node type.
    $this->entityTypeManager->getStorage('node_type')->create([
      'type' => 'page',
      'name' => 'page',
    ])->save();

    $node = $node_storage->create([
      'type' => 'page',
      'title' => 'test',
    ]);
    $node->save();

    // Create comment field.
    $this->entityTypeManager->getStorage('field_storage_config')->create([
      'type' => 'text_long',
      'entity_type' => 'node',
      'field_name' => 'comment',
    ])->save();

    // Add comment field to page content.
    $this->entityTypeManager->getStorage('field_config')->create([
      'field_storage' => FieldStorageConfig::loadByName('node', 'comment'),
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Comment',
    ])->save();

    // Make two comments
    $comment1 = $comment_storage->create([
      'field_name' => 'comment',
      'comment_body' => 'test',
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'comment_type' => 'default',
    ])->save();

    $comment_storage->create([
      'field_name' => 'comment',
      'comment_body' => 'test',
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'comment_type' => 'default',
      'pid' => $comment1,
    ])->save();

    // Render the comments.
    $renderer = \Drupal::service('renderer');
    $comments = $comment_storage->loadMultiple();
    foreach ($comments as $comment) {
      $built = $this->buildEntityView($comment, 'full', NULL);
      $renderer->renderPlain($built);
    }

    // Make comment 2 an orphan by setting the property to an invalid value.
    \Drupal::database()->update('comment_field_data')
      ->fields([$property => 10])
      ->condition('cid', 2)
      ->execute();
    $comment_storage->resetCache();
    $node_storage->resetCache();

    // Render the comments with an orphan comment.
    $comments = $comment_storage->loadMultiple();
    foreach ($comments as $comment) {
      $built = $this->buildEntityView($comment, 'full', NULL);
      $renderer->renderPlain($built);
    }

    $node = $node_storage->load($node->id());
    $built = $this->buildEntityView($node, 'full', NULL);
    $renderer->renderPlain($built);
  }

  /**
   * Provides test data for testOrphan.
   */
  public function providerTestOrphan() {
    return [
      ['entity_id'],
      ['uid'],
      ['pid'],
    ];
  }

}
