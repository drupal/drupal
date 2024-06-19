<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Kernel\Views;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Render\RenderContext;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the comment field name field.
 *
 * @group comment
 */
class CommentFieldNameTest extends KernelTestBase {

  use CommentTestTrait;
  use NodeCreationTrait;
  use UserCreationTrait;
  use ViewResultAssertionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'comment_test_views',
    'field',
    'filter',
    'node',
    'system',
    'text',
    'user',
    'views',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_comment_field_name'];

  /**
   * Tests comment field name.
   */
  public function testCommentFieldName(): void {
    $renderer = $this->container->get('renderer');

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installConfig(['filter']);

    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();
    FieldStorageConfig::create([
      'type' => 'text_long',
      'entity_type' => 'comment',
      'field_name' => 'comment_body',
    ])->save();
    $this->addDefaultCommentField('node', 'page', 'comment');
    $this->addDefaultCommentField('node', 'page', 'comment_custom');

    ViewTestData::createTestViews(static::class, ['comment_test_views']);

    $node = $this->createNode();
    $comment = Comment::create([
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
    ]);
    $comment->save();
    $comment2 = Comment::create([
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment_custom',
    ]);
    $comment2->save();

    $view = Views::getView('test_comment_field_name');
    $view->preview();

    $expected_result = [
      [
        'cid' => $comment->id(),
        'field_name' => $comment->getFieldName(),
      ],
      [
        'cid' => $comment2->id(),
        'field_name' => $comment2->getFieldName(),
      ],
    ];
    $column_map = [
      'cid' => 'cid',
      'comment_field_data_field_name' => 'field_name',
    ];
    $this->assertIdenticalResultset($view, $expected_result, $column_map);

    // Test that data rendered correctly.
    $expected_output = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['field_name']->advancedRender($view->result[0]);
    });
    $this->assertEquals($expected_output, $comment->getFieldName());
    $expected_output = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['field_name']->advancedRender($view->result[1]);
    });
    $this->assertEquals($expected_output, $comment2->getFieldName());
  }

}
