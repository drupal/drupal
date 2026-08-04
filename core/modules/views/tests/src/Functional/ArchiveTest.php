<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional;

use Drupal\views\Views;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests archive view.
 */
#[Group('views')]
#[RunTestsInSeparateProcesses]
class ArchiveTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'views_test_config'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views to be enabled.
   *
   * @var array
   */
  public static $testViews = ['test_archive'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    // Create Basic page node type.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Create a time in the past for the archive.
    $time = \Drupal::time()->getRequestTime() - 3600;

    for ($i = 0; $i <= 10; $i++) {
      $values = ['created' => $time, 'type' => 'page'];
      $this->drupalCreateNode($values);
    }
  }

  /**
   * Tests the test archive view.
   */
  public function testArchiveView(): void {
    // Create additional nodes compared to the one in the setup method.
    // Create two nodes in the same month, and one in each following month.
    $node = [
      // Sun, 19 Nov 1978 05:00:00 GMT.
      'created' => 280299600,
    ];
    $this->drupalCreateNode($node);
    $this->drupalCreateNode($node);
    $node = [
      // Tue, 19 Dec 1978 05:00:00 GMT.
      'created' => 282891600,
    ];
    $this->drupalCreateNode($node);
    $node = [
      // Fri, 19 Jan 1979 05:00:00 GMT.
      'created' => 285570000,
    ];
    $this->drupalCreateNode($node);

    $view = Views::getView('test_archive');
    $view->setDisplay('page_1');
    $this->executeView($view);
    $columns = ['nid', 'created_year_month', 'num_records'];
    $column_map = array_combine($columns, $columns);
    // Create time of additional nodes created in the setup method.
    $created_year_month = date('Ym', \Drupal::time()->getRequestTime() - 3600);
    $expected_result = [
      [
        'nid' => 1,
        'created_year_month' => $created_year_month,
        'num_records' => 11,
      ],
      [
        'nid' => 15,
        'created_year_month' => 197901,
        'num_records' => 1,
      ],
      [
        'nid' => 14,
        'created_year_month' => 197812,
        'num_records' => 1,
      ],
      [
        'nid' => 12,
        'created_year_month' => 197811,
        'num_records' => 2,
      ],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $column_map);

    $view->storage->setStatus(TRUE);
    $view->save();
    \Drupal::service('router.builder')->rebuild();

    $this->drupalGet('test_archive');
    $this->assertSession()->statusCodeEquals(200);
  }

}
