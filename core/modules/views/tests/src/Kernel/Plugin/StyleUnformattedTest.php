<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\views\Views;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests unformatted style functionality.
 */
#[Group('views')]
#[RunTestsInSeparateProcesses]
class StyleUnformattedTest extends StyleTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * Make sure that the default css classes works as expected.
   */
  public function testDefaultRowClasses(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();
    $output = $view->preview();
    $this->storeViewPreview(\Drupal::service('renderer')->renderRoot($output));

    $rows = $this->elements->body->div->div;
    $count = 0;
    $count_result = count($view->result);
    foreach ($rows as $row) {
      $count++;
      $attributes = $row->attributes();
      $class = (string) $attributes['class'][0];
      $this->assertStringContainsString('views-row', $class, 'Make sure that the views row class is set right.');
    }
    $this->assertSame($count, $count_result);
  }

}
