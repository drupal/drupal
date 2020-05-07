<?php

namespace Drupal\Tests\node\Functional\Views;

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
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_node_row_plugin'];

  /**
   * Contains all nodes used by this test.
   *
   * @var array
   */
  protected $nodes;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->drupalCreateContentType(['type' => 'article']);

    // Create two nodes.
    for ($i = 0; $i < 2; $i++) {
      $this->nodes[] = $this->drupalCreateNode(
        [
          'type' => 'article',
          'body' => [
            [
              'value' => $this->randomMachineName(42),
              'format' => filter_default_format(),
              'summary' => $this->randomMachineName(),
            ],
          ],
        ]
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
      $this->assertStringNotContainsString($node->body->summary, $output, 'Make sure the teaser appears in the output of the view.');
      $this->assertStringContainsString($node->body->value, $output, 'Make sure the full text appears in the output of the view.');
    }

    // Test with teasers.
    $view->rowPlugin->options['view_mode'] = 'teaser';
    $output = $view->preview();
    $output = $renderer->renderRoot($output);
    foreach ($this->nodes as $node) {
      $this->assertStringContainsString($node->body->summary, $output, 'Make sure the teaser appears in the output of the view.');
      $this->assertStringNotContainsString($node->body->value, $output, 'Make sure the full text does not appears in the output of the view if teaser is set as viewmode.');
    }
  }

}
