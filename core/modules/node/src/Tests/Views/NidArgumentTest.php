<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Views\NidArgumentTest.
 */

namespace Drupal\node\Tests\Views;

use Drupal\node\Entity\Node;
use Drupal\views\Tests\ViewKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the nid argument handler.
 *
 * @group node
 * @see \Drupal\node\Plugin\views\argument\Nid
 */
class NidArgumentTest extends ViewKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'field', 'text', 'node_test_config', 'user', 'entity_reference', 'node_test_views'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_nid_argument'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig(['node', 'field']);

    ViewTestData::createTestViews(get_class($this), ['node_test_views']);
  }

  /**
   * Test the nid argument.
   */
  public function testNidArgument() {
    $view = Views::getView('test_nid_argument');
    $view->setDisplay();

    $node1 = Node::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
    ]);
    $node1->save();
    $node2 = Node::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
    ]);
    $node2->save();

    $view->preview();
    $this->assertEqual(count($view->result), 2, 'Found the expected number of results.');

    // Set an the second node id as an argument.
    $view->destroy();
    $view->preview('default', [$node2->id()]);
    // Verify that the title is overridden.
    $this->assertEqual($view->getTitle(), $node2->getTitle());
    // Verify that the argument filtering works.
    $this->assertEqual(count($view->result), 1, 'Found the expected number of results.');
    $this->assertEqual($node2->id(), (string) $view->style_plugin->getField(0, 'nid'), 'Found the correct nid.');

    // Verify that setting a non-existing id as argument results in no nodes
    // being shown.
    $view->destroy();
    $view->preview('default', [22]);
    $this->assertEqual(count($view->result), 0, 'Found the expected number of results.');
  }

}
