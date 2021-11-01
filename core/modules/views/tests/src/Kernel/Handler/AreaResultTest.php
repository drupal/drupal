<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the result area handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\area\Result
 */
class AreaResultTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_area_result'];

  /**
   * Tests the results area handler.
   */
  public function testResult() {
    $view = Views::getView('test_area_result');
    $view->setDisplay('default');
    $this->executeView($view);
    $output = $view->render();
    $output = \Drupal::service('renderer')->renderRoot($output);
    $this->setRawContent($output);
    $this->assertText('start: 1 | end: 5 | total: 5 | label: test_area_result | per page: 0 | current page: 1 | current record count: 5 | page count: 1');

    // Make sure that potentially dangerous content was stripped.
    $this->assertNoRaw('<script />');
  }

  /**
   * Tests the results area handler.
   */
  public function testResultEmpty() {
    $view = Views::getView('test_area_result');

    // Test that the area is displayed if we have checked the empty checkbox.
    $view->setDisplay('default');

    // Add a filter that will make the result set empty.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'operator' => '=',
        'value' => 'non-existing-name',
      ],
    ]);

    $this->executeView($view);
    $output = $view->render();
    $output = \Drupal::service('renderer')->renderRoot($output);
    $this->setRawContent($output);
    $this->assertText('start: 0 | end: 0 | total: 0 | label: test_area_result | per page: 0 | current page: 1 | current record count: 0 | page count: 1');
    $this->assertRaw('<header>');

    // Test that the area is not displayed if we have not checked the empty
    // checkbox.
    $view->setDisplay('page_1');

    $this->executeView($view);
    $output = $view->render();
    $output = \Drupal::service('renderer')->renderRoot($output);
    $this->setRawContent($output);
    $this->assertNoText('start: 0 | end: 0 | total: 0 | label: test_area_result | per page: 0 | current page: 1 | current record count: 0 | page count: 1');
    // Make sure the empty header region isn't rendered.
    $this->assertNoRaw('<header>');
  }

}
