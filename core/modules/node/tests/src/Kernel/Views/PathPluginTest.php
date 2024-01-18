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
 */
class PathPluginTest extends ViewsKernelTestBase {

  use NodeCreationTrait;
  use UserCreationTrait;

  /**
   * Views used by this test.
   *
   * @var string[]
   */
  public static $testViews = ['test_node_path_plugin'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'node_test_views',
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
    ViewTestData::createTestViews(static::class, ['node_test_views']);

    \Drupal::currentUser()->setAccount($this->createUser(['access content']));

    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    // Create two nodes.
    for ($i = 0; $i < 2; $i++) {
      $this->nodes[] = $this->createNode(['type' => 'article']);
    }
  }

  /**
   * Tests the node path plugin functionality when converted to entity link.
   */
  public function testPathPlugin(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $view = Views::getView('test_node_path_plugin');

    // The configured deprecated node path plugin should be converted to the
    // entity link plugin.
    $field = $view->getHandler('page_1', 'field', 'path');
    $this->assertEquals('entity_link', $field['plugin_id']);

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
