<?php

namespace Drupal\node\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the node row plugin.
 *
 * @group node
 */
class PathPluginTest extends NodeTestBase {

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
  public static $testViews = array('test_node_path_plugin');

  /**
   * Contains all nodes used by this test.
   *
   * @var Node[]
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
   * Tests the node path plugin.
   */
  public function testPathPlugin() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $view = Views::getView('test_node_path_plugin');
    $view->initDisplay();
    $view->setDisplay('page_1');
    $view->initStyle();
    $view->rowPlugin->options['view_mode'] = 'full';

    // Test with view_mode full.
    $output = $view->preview();
    $output = $renderer->renderRoot($output);
    foreach ($this->nodes as $node) {
      $this->assertTrue(strpos($output, 'This is <strong>not escaped</strong> and this is ' . $node->link('the link')) !== FALSE, 'Make sure path field rewriting is not escaped.');
    }
  }

}
