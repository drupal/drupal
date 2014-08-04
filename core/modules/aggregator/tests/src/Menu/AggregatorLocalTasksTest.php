<?php

/**
 * @file
 * Contains \Drupal\aggregator\Tests\Menu\AggregatorLocalTasksTest.
 */

namespace Drupal\aggregator\Tests\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;

/**
 * Tests existence of aggregator local tasks.
 *
 * @group aggregator
 */
class AggregatorLocalTasksTest extends LocalTaskIntegrationTest {

  public function setUp() {
    $this->directoryList = array('aggregator' => 'core/modules/aggregator');
    parent::setUp();
  }

  /**
   * Tests local task existence.
   *
   * @dataProvider getAggregatorAdminRoutes
   */
  public function testAggregatorAdminLocalTasks($route) {
    $this->assertLocalTasks($route, array(
      0 => array('aggregator.admin_overview', 'aggregator.admin_settings'),
    ));
  }

  /**
   * Provides a list of routes to test.
   */
  public function getAggregatorAdminRoutes() {
    return array(
      array('aggregator.admin_overview'),
      array('aggregator.admin_settings'),
    );
  }

  /**
   * Checks aggregator source tasks.
   *
   * @dataProvider getAggregatorSourceRoutes
   */
  public function testAggregatorSourceLocalTasks($route) {
    $this->assertLocalTasks($route, array(
      0 => array('entity.aggregator_feed.canonical', 'entity.aggregator_feed.edit_form'),
    ));
    ;
  }

  /**
   * Provides a list of source routes to test.
   */
  public function getAggregatorSourceRoutes() {
    return array(
      array('entity.aggregator_feed.canonical'),
      array('entity.aggregator_feed.edit_form'),
    );
  }

}
