<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel\Views;

use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the node row plugin.
 *
 * @see \Drupal\node\Plugin\views\row\NodeRow
 */
#[Group('node')]
#[RunTestsInSeparateProcesses]
class RowPluginTest extends ViewsKernelTestBase {

  use ContentTypeCreationTrait;
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

    $this->createContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    $display = \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'article', 'teaser');
    $display_options = $display->getComponent('body');
    $display_options['settings']['trim_length'] = 25;
    $display->setComponent('body', $display_options)
      ->save();

    // Create two nodes.
    for ($i = 0; $i < 2; $i++) {
      $this->nodes[] = $this->createNode(
        [
          'type' => 'article',
          'body' => [
            [
              'value' => $this->randomMachineName(42),
              'format' => filter_default_format(),
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
      $this->assertStringContainsString($node->body->value, $output, 'Make sure the full text appears in the output of the view.');
    }

    // Test with teasers.
    $view->rowPlugin->options['view_mode'] = 'teaser';
    $output = $view->preview();
    $output = (string) $renderer->renderRoot($output);
    foreach ($this->nodes as $node) {
      // Using 22 because <p> is being included.
      $this->assertStringNotContainsString(substr($node->body->value, 22), $output);
    }
  }

}
