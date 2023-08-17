<?php

namespace Drupal\Tests\node\Kernel\Views;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the node_revision_uid field.
 *
 * @group node
 */
class RevisionUidTest extends ViewsKernelTestBase {

  use UserCreationTrait;
  use ViewResultAssertionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'node_test_views',
    'system',
    'user',
    'views',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_node_revision_uid'];

  /**
   * Map column names.
   *
   * @var array
   */
  public static $columnMap = [
    'nid' => 'nid',
    'vid' => 'vid',
    'uid' => 'uid',
    'revision_uid' => 'revision_uid',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    if ($import_test_views) {
      ViewTestData::createTestViews(static::class, ['node_test_views']);
    }
  }

  /**
   * Tests the node_revision_uid relationship.
   */
  public function testRevisionUid() {
    $primary_author = $this->createUser();
    $secondary_author = $this->createUser();

    $node_type = NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ]);
    $node_type->save();
    $node = Node::create([
      'title' => 'Test node',
      'type' => 'page',
      'uid' => $primary_author->id(),
    ]);
    $node->save();
    $view = Views::getView('test_node_revision_uid');
    $this->executeView($view);
    $this->assertIdenticalResultset($view, [
      [
        'nid' => 1,
        'vid' => 1,
        'uid' => $primary_author->id(),
        'revision_uid' => $primary_author->id(),
      ],
    ], static::$columnMap);

    // Test results shows the original author as well as the revision author.
    $node->setRevisionUser($secondary_author);
    $node->setNewRevision();
    $node->save();

    $view = Views::getView('test_node_revision_uid');
    $this->executeView($view);
    $this->assertIdenticalResultset($view, [
      [
        'nid' => 1,
        'vid' => 2,
        'uid' => $primary_author->id(),
        'revision_uid' => $secondary_author->id(),
      ],
    ], static::$columnMap);

    // Build a larger dataset to allow filtering.
    $node2_title = $this->randomString();
    $node2 = Node::create([
      'title' => $node2_title,
      'type' => 'page',
      'uid' => $primary_author->id(),
    ]);
    $node2->save();
    $node2->setRevisionUser($primary_author);
    $node2->setNewRevision();
    $node2->save();

    $view = Views::getView('test_node_revision_uid');
    $this->executeView($view);
    $this->assertIdenticalResultset($view, [
      [
        'nid' => 1,
        'vid' => 2,
        'uid' => $primary_author->id(),
        'revision_uid' => $secondary_author->id(),
      ],
      [
        'nid' => 2,
        'vid' => 4,
        'uid' => $primary_author->id(),
        'revision_uid' => $primary_author->id(),
      ],
    ], static::$columnMap);

    // Test filter by revision_uid.
    $view = Views::getView('test_node_revision_uid');
    $view->initHandlers();
    $view->filter['revision_uid']->value = [$secondary_author->id()];
    $this->executeView($view);
    $this->assertIdenticalResultset($view, [
      [
        'nid' => 1,
        'vid' => 2,
        'uid' => $primary_author->id(),
        'revision_uid' => $secondary_author->id(),
      ],
    ], static::$columnMap);
  }

}
