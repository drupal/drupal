<?php

namespace Drupal\Tests\node\Kernel\Action;

use Drupal\Core\Render\RenderContext;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\system\Entity\Action;

/**
 * @group node
 */
class UnpublishByKeywordActionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['action', 'node', 'system', 'user', 'field'];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', ['node_access']);
    // Install system's configuration as default date formats are needed.
    $this->installConfig(['system']);
    // Create a node type for testing.
    $type = NodeType::create(['type' => 'page', 'name' => 'page', 'display_submitted' => FALSE]);
    $type->save();
  }

  /**
   * Tests creating an action using the node_unpublish_by_keyword_action plugin.
   */
  public function testUnpublishByKeywordAction() {
    /** @var \Drupal\node\Plugin\Action\UnpublishByKeywordNode $action */
    $action = Action::create([
      'id' => 'foo',
      'label' => 'Foo',
      'plugin' => 'node_unpublish_by_keyword_action',
      'configuration' => [
        'keywords' => ['test'],
      ],
    ]);
    $action->save();
    $node1 = Node::create([
      'type' => 'page',
      'title' => 'test',
      'uid' => 1,
    ]);
    $node1->setPublished();
    $node1->save();
    $node2 = Node::create([
      'type' => 'page',
      'title' => 'Another node',
      'uid' => 1,
    ]);
    $node2->setPublished();
    $node2->save();

    $this->container->get('renderer')->executeInRenderContext(new RenderContext(), function () use (&$node1, &$node2, $action) {
      $action->execute([$node1, $node2]);
    });

    $this->assertFalse($node1->isPublished());
    $this->assertTrue($node2->isPublished());
  }

}
