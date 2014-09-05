<?php

/**
 * @file
 * Contains \Drupal\Tests\taxonomy\Unit\Menu\TaxonomyLocalTasksTest.
 */

namespace Drupal\Tests\taxonomy\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;

/**
 * Tests existence of taxonomy local tasks.
 *
 * @group taxonomy
 */
class TaxonomyLocalTasksTest extends LocalTaskIntegrationTest {

  protected function setUp() {
    $this->directoryList = array('taxonomy' => 'core/modules/taxonomy');
    parent::setUp();
  }

  /**
   * Checks taxonomy edit local tasks.
   *
   * @dataProvider getTaxonomyPageRoutes
   */
  public function testTaxonomyPageLocalTasks($route, $subtask = array()) {
    $tasks = array(
      0 => array('entity.taxonomy_term.canonical', 'entity.taxonomy_term.edit_form'),
    );
    if ($subtask) $tasks[] = $subtask;
    $this->assertLocalTasks($route, $tasks);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getTaxonomyPageRoutes() {
    return array(
      array('entity.taxonomy_term.canonical'),
      array('entity.taxonomy_term.edit_form'),
    );
  }

}
