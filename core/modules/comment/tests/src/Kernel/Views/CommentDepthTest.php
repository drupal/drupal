<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Kernel\Views;

use Drupal\comment\CommentManagerInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\views\Views;

/**
 * Tests the depth of the comment field handler.
 *
 * @group comment
 */
class CommentDepthTest extends CommentViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_comment'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('entity_test');
  }

  /**
   * Test the comment depth.
   */
  public function testCommentDepth(): void {
    $this->enableModules(['field']);
    $this->installConfig(['field']);

    // Create a comment field storage.
    $field_storage_comment = FieldStorageConfig::create([
      'field_name' => 'comment',
      'type' => 'comment',
      'entity_type' => 'entity_test',
    ]);
    $field_storage_comment->save();

    // Create a comment field which allows threading.
    $field_comment = FieldConfig::create([
      'field_name' => 'comment',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'settings' => [
        'default_mode' => CommentManagerInterface::COMMENT_MODE_THREADED,
      ],
    ]);
    $field_comment->save();

    // Create a test entity.
    $host = EntityTest::create(['name' => $this->randomString()]);
    $host->save();

    // Create the thread of comments.
    $comment1 = $this->commentStorage->create([
      'uid' => $this->adminUser->id(),
      'entity_type' => 'entity_test',
      'entity_id' => $host->id(),
      'comment_type' => 'entity_test',
      'field_name' => $field_storage_comment->getName(),
      'status' => 1,
    ]);
    $comment1->save();

    $comment2 = $this->commentStorage->create([
      'uid' => $this->adminUser->id(),
      'entity_type' => 'entity_test',
      'entity_id' => $host->id(),
      'comment_type' => 'entity_test',
      'field_name' => $field_storage_comment->getName(),
      'status' => 1,
      'pid' => $comment1->id(),
    ]);
    $comment2->save();

    $comment3 = $this->commentStorage->create([
      'uid' => $this->adminUser->id(),
      'entity_type' => 'entity_test',
      'entity_id' => $host->id(),
      'comment_type' => 'entity_test',
      'field_name' => $field_storage_comment->getName(),
      'status' => 1,
      'pid' => $comment2->id(),
    ]);
    $comment3->save();

    $view = Views::getView('test_comment');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('fields', [
      'thread' => [
        'table' => 'comment_field_data',
        'field' => 'thread',
        'id' => 'thread',
        'plugin_id' => 'comment_depth',
        'entity_type' => 'comment',
      ],
    ]);
    $view->save();

    $view->preview();

    // Check if the depth of the first comment is 0.
    $comment1_depth = $view->style_plugin->getField(0, 'thread');
    $this->assertEquals(0, (string) $comment1_depth, "The depth of the first comment is 0.");

    // Check if the depth of the first comment is 1.
    $comment2_depth = $view->style_plugin->getField(1, 'thread');
    $this->assertEquals(1, (string) $comment2_depth, "The depth of the second comment is 1.");

    // Check if the depth of the first comment is 2.
    $comment3_depth = $view->style_plugin->getField(2, 'thread');
    $this->assertEquals(2, (string) $comment3_depth, "The depth of the third comment is 2.");
  }

}
