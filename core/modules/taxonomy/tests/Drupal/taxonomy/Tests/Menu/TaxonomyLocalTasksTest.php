<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\TaxonomyLocalTasksTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;

/**
 * Tests existence of taxonomy local tasks.
 *
 * @group Drupal
 * @group Taxonomy
 */
class TaxonomyLocalTasksTest extends LocalTaskIntegrationTest {

  public static function getInfo() {
    return array(
      'name' => 'Taxonomy local tasks test',
      'description' => 'Test existence of taxonomy local tasks.',
      'group' => 'Taxonomy',
    );
  }

  public function setUp() {
    $this->moduleList = array('taxonomy' => 'core/modules/taxonomy/taxonomy.module');
    parent::setUp();
  }

  /**
   * Checks taxonomy edit local tasks.
   *
   * @dataProvider getTaxonomyPageRoutes
   */
  public function testTaxonomyPageLocalTasks($route, $subtask = array()) {
    $tasks = array(
      0 => array('taxonomy.term_page', 'taxonomy.term_edit'),
    );
    if ($subtask) $tasks[] = $subtask;
    $this->assertLocalTasks($route, $tasks);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getTaxonomyPageRoutes() {
    return array(
      array('taxonomy.term_page'),
      array('taxonomy.term_edit'),
    );
  }

}
