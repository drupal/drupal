<?php

namespace Drupal\Tests\comment\Kernel\Views;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the user posted or commented filter and argument handlers.
 *
 * @group comment
 */
class FilterAndArgumentUserUidTest extends KernelTestBase {

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
  public static $testViews = ['test_comment_user_uid'];

  /**
   * Tests the user posted or commented filter and argument handlers.
   */
  public function testHandlers() {
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

    $account = $this->createUser();
    $other_account = $this->createUser();

    $node_authored_by_account = $this->createNode([
      'uid' => $account->id(),
      'title' => "authored by {$account->id()}",
    ]);
    $node_commented_by_account = $this->createNode([
      'title' => "commented by {$account->id()}",
    ]);
    $arbitrary_node = $this->createNode();

    // Comment added by $account.
    Comment::create([
      'uid' => $account->id(),
      'entity_id' => $node_commented_by_account->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
    ])->save();
    // Comment added by $other_account on $node_commented_by_account
    Comment::create([
      'uid' => $other_account->id(),
      'entity_id' => $node_commented_by_account->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
    ])->save();
    // Comment added by $other_account on an arbitrary node.
    Comment::create([
      'uid' => $other_account->id(),
      'entity_id' => $arbitrary_node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
    ])->save();

    ViewTestData::createTestViews(static::class, ['comment_test_views']);

    $expected_result = [
      [
        'nid' => $node_authored_by_account->id(),
        'title' => "authored by {$account->id()}",
      ],
      [
        'nid' => $node_commented_by_account->id(),
        'title' => "commented by {$account->id()}",
      ],
    ];
    $column_map = ['nid' => 'nid', 'title' => 'title'];
    $view = Views::getView('test_comment_user_uid');

    // Test the argument handler.
    $view->preview(NULL, [$account->id()]);
    $this->assertIdenticalResultset($view, $expected_result, $column_map);

    // Test the filter handler. Reuse the same view but replace the argument
    // handler with a filter handler.
    $view->removeHandler('default', 'argument', 'uid_touch');
    $options = [
      'id' => 'uid_touch',
      'table' => 'node_field_data',
      'field' => 'uid_touch',
      'value' => [$account->id()],
    ];
    $view->addHandler('default', 'filter', 'node_field_data', 'uid_touch', $options);

    $view->preview();
    $this->assertIdenticalResultset($view, $expected_result, $column_map);
  }

}
