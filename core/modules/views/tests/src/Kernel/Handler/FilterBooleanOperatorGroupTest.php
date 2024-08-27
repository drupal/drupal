<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\filter\BooleanOperator handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\filter\BooleanOperator
 */
class FilterBooleanOperatorGroupTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field',
    'text',
    'node',
    'user',
    'views_test_config',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_boolean_grouped_filter_view'];

  /**
   * {@inheritdoc}
   */
  public function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['node']);

    $node_type = NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ]);
    $node_type->setDisplaySubmitted(FALSE);
    $node_type->save();

    FieldStorageConfig::create([
      'entity_type' => 'node',
      'type' => 'boolean',
      'field_name' => 'field_test_boolean_field',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'page',
      'field_name' => 'field_test_boolean_field',
    ])->save();

    Node::create([
      'title' => 'Checked',
      'type' => 'page',
      'field_test_boolean_field' => 1,
      'status' => TRUE,
    ])->save();

    Node::create([
      'title' => 'Un-checked',
      'type' => 'page',
      'field_test_boolean_field' => 0,
      'status' => TRUE,
    ])->save();
  }

  /**
   * Tests that grouped boolean exposed form works as expected.
   */
  public function testViewsBooleanGroupedFilter(): void {
    /** @var \Drupal\views\ViewExecutable $view */
    $view = Views::getView('test_boolean_grouped_filter_view');
    $view->setDisplay('page_1');
    $view->setExposedInput(['field_test_boolean_field_value' => 'All']);
    $view->execute();
    $this->assertEquals(2, count($view->result));

    $build = $view->rowPlugin->render($view->result[0]);
    $output = \Drupal::service('renderer')->renderRoot($build);
    $this->assertStringContainsString('Checked', $output->__toString());

    $build = $view->rowPlugin->render($view->result[1]);
    $output = \Drupal::service('renderer')->renderRoot($build);
    $this->assertStringContainsString('Un-checked', $output->__toString());

    $view = Views::getView('test_boolean_grouped_filter_view');
    $view->setDisplay('page_1');
    $view->setExposedInput(['field_test_boolean_field_value' => 1]);
    $view->execute();
    $this->assertEquals(1, count($view->result));
    $build = $view->rowPlugin->render($view->result[0]);
    $output = \Drupal::service('renderer')->renderRoot($build);
    $this->assertStringContainsString('Checked', $output->__toString());

    $view = Views::getView('test_boolean_grouped_filter_view');
    $view->setDisplay('page_1');
    $view->setExposedInput(['field_test_boolean_field_value' => '2']);
    $view->execute();
    $this->assertEquals(1, count($view->result));
    $build = $view->rowPlugin->render($view->result[0]);
    $output = \Drupal::service('renderer')->renderRoot($build);
    $this->assertStringContainsString('Un-checked', $output->__toString());
  }

}
