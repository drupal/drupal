<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel\Views;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the node_vid handler.
 *
 * @group node
 */
class ArgumentNodeRevisionIdTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'field', 'user', 'node_test_views'];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_node_revision_id_argument'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);
    $this->installSchema('node', 'node_access');

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    ViewTestData::createTestViews(get_class($this), ['node_test_views']);
  }

  /**
   * Tests the node revision id argument via the node_vid handler.
   */
  public function testNodeRevisionRelationship(): void {
    NodeType::create(['type' => 'page', 'name' => 'page'])->save();
    $node = Node::create(['type' => 'page', 'title' => 'test1', 'uid' => 1]);
    $node->save();
    $node->setNewRevision();
    $node->setTitle('test2');
    $node->save();
    $second_revision_id = $node->getRevisionId();

    $view_nid = Views::getView('test_node_revision_id_argument');
    $this->executeView($view_nid, [$second_revision_id]);
    $this->assertIdenticalResultset($view_nid, [['title' => 'test2']]);
    $this->assertSame('test2', $view_nid->getTitle());
  }

}
