<?php

namespace Drupal\Tests\node\Kernel\Views;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the node row plugin.
 *
 * @group node
 * @see \Drupal\node\Plugin\views\row\NodeRow
 */
class RowPluginTest extends ViewsKernelTestBase {

  use NodeCreationTrait;
  use UserCreationTrait;

  /**
   * Views used by this test.
   *
   * @var string[]
   */
  public static $testViews = ['test_node_row_plugin'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'filter',
    'node',
    'node_test_views',
    'text',
    'user',
  ];

  /**
   * Contains all nodes used by this test.
   *
   * @var \Drupal\node\Entity\Node[]
   */
  protected array $nodes;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['field', 'filter', 'node']);
    ViewTestData::createTestViews(static::class, ['node_test_views']);

    \Drupal::currentUser()->setAccount($this->createUser(['access content']));

    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node_type->save();
    node_add_body_field($node_type);

    // Create two nodes.
    for ($i = 0; $i < 2; $i++) {
      $this->nodes[] = $this->createNode(
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
  public function testRowPlugin(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $view = Views::getView('test_node_row_plugin');
    $view->initDisplay();
    $view->setDisplay('page_1');
    $view->initStyle();
    $view->rowPlugin->options['view_mode'] = 'full';

    // Test with view_mode full.
    $output = $view->preview();
    $output = (string) $renderer->renderRoot($output);
    foreach ($this->nodes as $node) {
      $this->assertStringNotContainsString($node->body->summary, $output, 'Make sure the teaser appears in the output of the view.');
      $this->assertStringContainsString($node->body->value, $output, 'Make sure the full text appears in the output of the view.');
    }

    // Test with teasers.
    $view->rowPlugin->options['view_mode'] = 'teaser';
    $output = $view->preview();
    $output = (string) $renderer->renderRoot($output);
    foreach ($this->nodes as $node) {
      $this->assertStringContainsString($node->body->summary, $output, 'Make sure the teaser appears in the output of the view.');
      $this->assertStringNotContainsString($node->body->value, $output);
    }
  }

}
