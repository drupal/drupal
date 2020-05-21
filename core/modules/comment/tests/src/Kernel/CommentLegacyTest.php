<?php

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests legacy comment functionality.
 *
 * @group comment
 * @group legacy
 */
class CommentLegacyTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['comment'];

  /**
   * The entity to which the comments are attached to.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('comment');
    $this->installSchema('comment', ['comment_entity_statistics']);
    CommentType::create([
      'id' => 'comment',
      'label' => $this->randomString(),
      'target_entity_type_id' => 'entity_test',
    ])->save();
    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'type' => 'comment',
      'field_name' => 'comments',
      'settings' => [
        'comment_type' => 'comment',
      ],
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field_name' => 'comments',
    ])->save();
    $this->entity = EntityTest::create(['name' => $this->randomString()]);
    $this->entity->save();
  }

  /**
   * Constructs comment entity.
   *
   * @return \Drupal\comment\CommentInterface
   *   Created comment entity.
   */
  protected function createComment() {
    return Comment::create([
      'entity_type' => 'entity_test',
      'field_name' => 'comments',
      'entity_id' => $this->entity->id(),
      'comment_type' => 'comment',
    ]);
  }

  /**
   * @expectedDeprecation comment_view() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal::entityTypeManager()->getViewBuilder('comment')->view() instead. See https://www.drupal.org/node/3033656
   * @expectedDeprecation comment_view_multiple() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal::entityTypeManager()->getViewBuilder('comment')->viewMultiple() instead. See https://www.drupal.org/node/3033656
   */
  public function testCommentView() {
    $entity = $this->createComment();
    $this->assertNotEmpty(comment_view($entity));
    $entities = [
      $this->createComment(),
      $this->createComment(),
    ];
    $this->assertCount(4, comment_view_multiple($entities));
  }

  /**
   * Tests the getStatus() method.
   *
   * @expectedDeprecation Drupal\comment\Entity\Comment::getStatus() is deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityPublishedInterface::isPublished() instead. See https://www.drupal.org/node/2830201
   */
  public function testGetStatus() {
    $entity = $this->createComment();
    $entity->setPublished();
    $this->assertEquals(CommentInterface::PUBLISHED, $entity->getStatus());
    $entity->setUnPublished();
    $this->assertEquals(CommentInterface::NOT_PUBLISHED, $entity->getStatus());
  }

}
