<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the "Display all values in the same row" setting.
 *
 * @see \Drupal\views\Plugin\views\field\EntityField
 *
 * @group views
 */
class FieldGroupRowsTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'filter',
    'node',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_group_rows', 'test_ungroup_rows'];

  /**
   * Testing the "Grouped rows" functionality.
   */
  public function testGroupRows(): void {
    $this->installConfig(['filter']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();

    // Create a text with unlimited cardinality.
    FieldStorageConfig::create([
      'type' => 'text',
      'entity_type' => 'node',
      'field_name' => 'field_group_rows',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'page',
      'field_name' => 'field_group_rows',
    ])->save();

    Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'field_group_rows' => ['a', 'b', 'c'],
    ])->save();

    $renderer = $this->container->get('renderer');
    $view = Views::getView('test_group_rows');

    // Test grouped rows.
    $this->executeView($view);
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['field_group_rows']->advancedRender($view->result[0]);
    });
    $this->assertEquals('a, b, c', $output);

    // Change the group_rows checkbox to false.
    $view = Views::getView('test_group_rows');
    $view->setHandlerOption('default', 'field', 'field_group_rows', 'group_rows', FALSE);

    // Test ungrouped rows.
    $this->executeView($view);
    $view->render();

    $view->row_index = 0;
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['field_group_rows']->advancedRender($view->result[0]);
    });
    $this->assertEquals('a', $output);
    $view->row_index = 1;
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['field_group_rows']->advancedRender($view->result[1]);
    });
    $this->assertEquals('b', $output);
    $view->row_index = 2;
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['field_group_rows']->advancedRender($view->result[2]);
    });
    $this->assertEquals('c', $output);
  }

}
