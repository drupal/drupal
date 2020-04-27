<?php

namespace Drupal\Tests\node\Functional\Views;

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
  protected static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_node_path_plugin'];

  /**
   * Contains all nodes used by this test.
   *
   * @var Node[]
   */
  protected $nodes;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
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
   * Tests the node path plugin functionality when converted to entity link.
   */
  public function testPathPlugin() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $view = Views::getView('test_node_path_plugin');

    // The configured deprecated node path plugin should be converted to the
    // entity link plugin.
    $field = $view->getHandler('page_1', 'field', 'path');
    $this->assertEqual('entity_link', $field['plugin_id']);

    $view->initDisplay();
    $view->setDisplay('page_1');
    $view->initStyle();
    $view->rowPlugin->options['view_mode'] = 'full';

    // Test with view_mode full.
    $output = $view->preview();
    $output = $renderer->renderRoot($output);
    foreach ($this->nodes as $node) {
      $this->assertStringContainsString('This is <strong>not escaped</strong> and this is ' . $node->toLink('the link')->toString(), $output, 'Make sure path field rewriting is not escaped.');
    }
  }

}
