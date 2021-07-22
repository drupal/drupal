<?php

namespace Drupal\Tests\views\Kernel\Plugin\field;

use Drupal\Core\Render\RenderContext;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests decimal formatter.
 *
 * @group views
 */
class FieldDecimalFormatterTest extends ViewsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['user', 'node', 'field', 'text', 'system'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_option_empty_zero'];

  /**
   * Machine name of decimal field.
   *
   * @var string
   */
  protected $field = 'field_price';

  /**
   * Node with decimal field.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', 'node_access');

    NodeType::create(['type' => 'test'])->save();

    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => $this->field,
      'type' => 'decimal',
      'settings' => [
          'precision' => 8,
          'scale' => 4,
          'prefix' => '(',
          'suffix' => ')',
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => $this->field,
      'entity_type' => 'node',
      'bundle' => 'test',
    ])->save();

    $node = Node::create([
      'type' => 'test',
      'title' => 'title',
    ]);
    $node->save();
    $this->node = $node;

    $this->renderer = \Drupal::service('renderer');
  }

  /**
   * Test that rows are not cached when the none cache plugin is used.
   */
  public function testOptionEmptyZero() {
    $view = Views::getView('test_option_empty_zero');
    $view->setDisplay();
    $view->initHandlers();

    $this->node->{$this->field} = 0.01;
    $this->node->save();

    $view->field[$this->field]->options['settings']['scale'] = 3;
    $this->executeView($view);
    $render = $this->getRenderRow($view);
    $this->assertNotEquals('', $render, 'By default, "" should not be treated as empty.');

    $view->field[$this->field]->options['settings']['scale'] = 1;
    $this->executeView($view);
    $render = $this->getRenderRow($view);
    $this->assertEquals('', $render, 'By default, "" should not be treated as empty.');

    $view->field[$this->field]->options['empty_zero'] = FALSE;
    $this->executeView($view);
    $render = $this->getRenderRow($view);
    $this->assertNotEquals('', $render, 'By default, "" should not be treated as empty.');
  }

  protected function getRenderRow($view, $index = 0) {
    return $this->renderer->executeInRenderContext(new RenderContext(),
      function () use ($view, $index) {
        return $view->field[$this->field]->advancedRender($view->result[$index]);
      }
    );
  }

}
