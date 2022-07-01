<?php

namespace Drupal\Tests\node\Kernel\Views;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\Plugin\views\argument\Vid;
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
  public function testNodeRevisionRelationship() {
    NodeType::create(['type' => 'page', 'name' => 'page'])->save();
    $node = Node::create(['type' => 'page', 'title' => 'test1', 'uid' => 1]);
    $node->save();
    $first_revision_id = $node->getRevisionId();
    $node->setNewRevision();
    $node->setTitle('test2');
    $node->save();
    $second_revision_id = $node->getRevisionId();

    $view_nid = Views::getView('test_node_revision_id_argument');
    $this->executeView($view_nid, [$second_revision_id]);
    $this->assertIdenticalResultset($view_nid, [['title' => 'test2']]);
    $this->assertSame('test2', $view_nid->getTitle());
  }

  /**
   * Tests the Vid argument deprecation.
   *
   * @group legacy
   */
  public function testVidDeprecatedParameter() {
    $this->expectDeprecation('Passing the database service to Drupal\node\Plugin\views\argument\Vid::__construct() is deprecated in drupal:9.2.0 and will be removed before drupal:10.0.0. See https://www.drupal.org/node/3178412');
    $database = $this->container->get('database');
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $vid = new Vid([], 'test_plugin', [], $database, $node_storage);
    $this->assertNotNull($vid);
  }

}
