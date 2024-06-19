<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;

/**
 * Tests existence of taxonomy local tasks.
 *
 * @group taxonomy
 */
class TaxonomyLocalTasksTest extends LocalTaskIntegrationTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->directoryList = ['taxonomy' => 'core/modules/taxonomy'];
    parent::setUp();
  }

  /**
   * Checks taxonomy edit local tasks.
   *
   * @dataProvider getTaxonomyPageRoutes
   */
  public function testTaxonomyPageLocalTasks($route, $subtask = []): void {
    $tasks = [
      0 => ['entity.taxonomy_term.canonical', 'entity.taxonomy_term.edit_form', 'entity.taxonomy_term.delete_form'],
    ];
    if ($subtask) {
      $tasks[] = $subtask;
    }
    $this->assertLocalTasks($route, $tasks);
  }

  /**
   * Provides a list of routes to test.
   */
  public static function getTaxonomyPageRoutes() {
    return [
      ['entity.taxonomy_term.canonical'],
      ['entity.taxonomy_term.edit_form'],
    ];
  }

}
