<?php

namespace Drupal\node\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the node row plugin.
 *
 * @group node
 * @see \Drupal\node\Plugin\views\row\NodeRow
 */
class RowPluginTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_node_row_plugin');

  /**
   * Contains all nodes used by this test.
   *
   * @var array
   */
  protected $nodes;

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'article'));

    // Create two nodes.
    for ($i = 0; $i < 2; $i++) {
      $this->nodes[] = $this->drupalCreateNode(
        array(
          'type' => 'article',
          'body' => array(
            array(
              'value' => $this->randomMachineName(42),
              'format' => filter_default_format(),
              'summary' => $this->randomMachineName(),
            ),
          ),
        )
      );
    }
  }

  /**
   * Tests the node row plugin.
   */
  public function testRowPlugin() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $view = Views::getView('test_node_row_plugin');
    $view->initDisplay();
    $view->setDisplay('page_1');
    $view->initStyle();
    $view->rowPlugin->options['view_mode'] = 'full';

    // Test with view_mode full.
    $output = $view->preview();
    $output = $renderer->renderRoot($output);
    foreach ($this->nodes as $node) {
      $this->assertFalse(strpos($output, $node->body->summary) !== FALSE, 'Make sure the teaser appears in the output of the view.');
      $this->assertTrue(strpos($output, $node->body->value) !== FALSE, 'Make sure the full text appears in the output of the view.');
    }

    // Test with teasers.
    $view->rowPlugin->options['view_mode'] = 'teaser';
    $output = $view->preview();
    $output = $renderer->renderRoot($output);
    foreach ($this->nodes as $node) {
      $this->assertTrue(strpos($output, $node->body->summary) !== FALSE, 'Make sure the teaser appears in the output of the view.');
      $this->assertFalse(strpos($output, $node->body->value) !== FALSE, 'Make sure the full text does not appears in the output of the view if teaser is set as viewmode.');
    }
  }

}
