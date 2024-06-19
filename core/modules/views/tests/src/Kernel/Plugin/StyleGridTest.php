<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\views\Views;
use Drupal\views\ViewExecutable;

/**
 * Tests the grid style plugin.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\style\Grid
 */
class StyleGridTest extends PluginKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_grid'];

  /**
   * Keeps track of which alignments have been tested.
   *
   * @var array
   */
  protected $alignmentsTested = [];

  /**
   * Tests the grid style.
   */
  public function testGrid(): void {
    $view = Views::getView('test_grid');
    foreach (['horizontal', 'vertical'] as $alignment) {
      $this->assertGrid($view, $alignment, 5);
      $this->assertGrid($view, $alignment, 4);
      $this->assertGrid($view, $alignment, 3);
      $this->assertGrid($view, $alignment, 2);
      $this->assertGrid($view, $alignment, 1);
    }
  }

  /**
   * Generates a grid and asserts that it is displaying correctly.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The executable to prepare.
   * @param string $alignment
   *   The alignment of the grid to test.
   * @param int $columns
   *   The number of columns in the grid to test.
   *
   * @internal
   */
  protected function assertGrid(ViewExecutable $view, string $alignment, int $columns): void {
    $view->setDisplay('default');
    $view->initStyle();
    $view->initHandlers();
    $view->initQuery();
    $view->style_plugin->options['alignment'] = $alignment;
    $view->style_plugin->options['columns'] = $columns;
    $this->executeView($view);
    $output = $view->preview();
    $output = \Drupal::service('renderer')->renderRoot($output);
    $this->setRawContent($output);
    if (!in_array($alignment, $this->alignmentsTested)) {
      $result = $this->xpath('//div[contains(@class, "views-view-grid") and contains(@class, :alignment) and contains(@class, :columns)]', [':alignment' => $alignment, ':columns' => 'cols-' . $columns]);
      $this->assertGreaterThan(0, count($result), ucfirst($alignment) . " grid markup detected.");
      $this->alignmentsTested[] = $alignment;
    }
    $width = '0';
    switch ($columns) {
      case 5: $width = '20';
        break;

      case 4: $width = '25';
        break;

      case 3: $width = '33.3333';
        break;

      case 2: $width = '50';
        break;

      case 1: $width = '100';
        break;
    }
    // Ensure last column exists.
    $result = $this->xpath('//div[contains(@class, "views-col") and contains(@class, :columns) and starts-with(@style, :width)]', [':columns' => 'col-' . $columns, ':width' => 'width: ' . $width]);
    $this->assertGreaterThan(0, count($result), ucfirst($alignment) . " $columns column grid: last column exists and automatic width calculated correctly.");
    // Ensure no extra columns were generated.
    $result = $this->xpath('//div[contains(@class, "views-col") and contains(@class, :columns)]', [':columns' => 'col-' . ($columns + 1)]);
    $this->assertEmpty($result, ucfirst($alignment) . " $columns column grid: no extraneous columns exist.");
    // Ensure tokens are being replaced in custom row/column classes.
    $result = $this->xpath('//div[contains(@class, "views-col") and contains(@class, "name-John")]');
    $this->assertGreaterThan(0, count($result), ucfirst($alignment) . " $columns column grid: Token replacement verified in custom column classes.");
    $result = $this->xpath('//div[contains(@class, "views-row") and contains(@class, "age-25")]');
    $this->assertGreaterThan(0, count($result), ucfirst($alignment) . " $columns column grid: Token replacement verified in custom row classes.");
  }

}
