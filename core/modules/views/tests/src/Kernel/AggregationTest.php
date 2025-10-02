<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel;

use Drupal\views\Views;

/**
 * A basic aggregation test for Views.
 *
 * @group views
 */
class AggregationTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['views.view.test_aggregation'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views_test_aggregation', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installConfig('views_test_aggregation');

    $this->installEntitySchema('user');
  }

  /**
   * Tests a trivial result set.
   */
  public function testSimpleResultSet(): void {
    $view = Views::getView('test_aggregation');
    $view->setDisplay();

    // Execute the view.
    $this->executeView($view);

    // Verify the result.
    $this->assertCount(1, $view->result, 'The number of returned rows match.');
  }

}
