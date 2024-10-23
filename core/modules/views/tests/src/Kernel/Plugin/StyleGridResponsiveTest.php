<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\views\Views;

/**
 * Tests the grid_responsive style plugin.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\style\GridResponsive
 */
class StyleGridResponsiveTest extends PluginKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_grid_responsive'];

  /**
   * Generates a grid_responsive and asserts that it is displaying correctly.
   *
   * @param array $options
   *   Options for the style plugin.
   * @param array $expected
   *   Expected values sued for assertions.
   *
   * @dataProvider providerTestResponsiveGrid
   */
  public function testResponsiveGrid(array $options, array $expected): void {
    // Create and preview a View with the provided options.
    $view = Views::getView('test_grid_responsive');
    $view->setDisplay('default');
    $view->initStyle();
    $view->initHandlers();
    $view->initQuery();
    $view->style_plugin->options = $options + $view->style_plugin->options;
    $this->executeView($view);
    $output = $view->preview();
    $output = \Drupal::service('renderer')->renderRoot($output);
    $this->setRawContent($output);

    // Confirm that the alignment class is added.
    $result = $this->xpath('//div[contains(@class, "views-view-responsive-grid") and contains(@class, :alignment)]', [':alignment' => 'views-view-responsive-grid--' . $expected['alignment']]);
    $this->assertGreaterThan(0, count($result), "Alignment CSS variable value is detected and correct.");

    // Check for CSS variables in style attribute.
    $result = $this->xpath('//div[contains(@class, "views-view-responsive-grid") and contains(@style, :columns)]', [':columns' => '--views-responsive-grid--column-count:' . $expected['columns']]);
    $this->assertGreaterThan(0, count($result), "Max-columns CSS variable value is detected and correct.");
    $result = $this->xpath('//div[contains(@class, "views-view-responsive-grid") and contains(@style, :min-width)]', [':min-width' => '--views-responsive-grid--cell-min-width:' . $expected['cell_min_width'] . 'px']);
    $this->assertGreaterThan(0, count($result), "Min-width CSS variable value is detected and correct.");
    $result = $this->xpath('//div[contains(@class, "views-view-responsive-grid") and contains(@style, :gutter)]', [':gutter' => '--views-responsive-grid--layout-gap:' . $expected['grid_gutter'] . 'px']);
    $this->assertGreaterThan(0, count($result), "Gutter CSS variable value is detected and correct.");

    // Assert that the correct number of elements have been rendered and that
    // markup structure is correct.
    $result = $this->xpath('//div[contains(@class, "views-view-responsive-grid")]/div[contains(@class, "views-view-responsive-grid__item")]/div[contains(@class, "views-view-responsive-grid__item-inner")]');
    // There are five results for this test view. See ViewTestData::dataSet().
    $this->assertCount(5, $result, "The expected number of items are rendered in the correct structure.");
  }

  /**
   * Data provider for testing various configurations.
   *
   * @return array
   *   Array containing options for the style plugin and expected values.
   */
  public static function providerTestResponsiveGrid() {
    return [
      'horizontal' => [
        'options' => [
          'columns' => 7,
          'cell_min_width' => 123,
          'grid_gutter' => 13,
          'alignment' => 'horizontal',
        ],
        'expected' => [
          'columns' => 7,
          'cell_min_width' => 123,
          'grid_gutter' => 13,
          'alignment' => 'horizontal',
        ],
      ],
      'vertical' => [
        'options' => [
          'columns' => 8,
          'cell_min_width' => 50,
          'grid_gutter' => 44,
          'alignment' => 'vertical',
        ],
        'expected' => [
          'columns' => 8,
          'cell_min_width' => 50,
          'grid_gutter' => 44,
          'alignment' => 'vertical',
        ],
      ],
      'default options' => [
        'options' => [],
        'expected' => [
          'columns' => 4,
          'cell_min_width' => 100,
          'grid_gutter' => 10,
          'alignment' => 'horizontal',
        ],
      ],
    ];
  }

}
