<?php

/**
 * @file
 * Contains \Drupal\Tests\update\Unit\Menu\UpdateLocalTasksTest.
 */

namespace Drupal\Tests\update\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;

/**
 * Tests existence of update local tasks.
 *
 * @group update
 */
class UpdateLocalTasksTest extends LocalTaskIntegrationTest {

  protected function setUp() {
    $this->directoryList = array('update' => 'core/modules/update');
    parent::setUp();
  }

  /**
   * Checks update report tasks.
   *
   * @dataProvider getUpdateReportRoutes
   */
  public function testUpdateReportLocalTasks($route) {
    $this->assertLocalTasks($route, array(
      0 => array('update.status', 'update.settings', 'update.report_update'),
    ));
  }

  /**
   * Provides a list of report routes to test.
   */
  public function getUpdateReportRoutes() {
    return array(
      array('update.status'),
      array('update.settings'),
      array('update.report_update'),
    );
  }

  /**
   * Checks update module tasks.
   *
   * @dataProvider getUpdateModuleRoutes
   */
  public function testUpdateModuleLocalTasks($route) {
    $this->assertLocalTasks($route, array(
      0 => array('update.module_update'),
    ));
    ;
  }

  /**
   * Provides a list of module routes to test.
   */
  public function getUpdateModuleRoutes() {
    return array(
      array('update.module_update'),
    );
  }

  /**
   * Checks update theme tasks.
   *
   * @dataProvider getUpdateThemeRoutes
   */
  public function testUpdateThemeLocalTasks($route) {
    $this->assertLocalTasks($route, array(
      0 => array('update.theme_update'),
    ));
    ;
  }

  /**
   * Provides a list of theme routes to test.
   */
  public function getUpdateThemeRoutes() {
    return array(
      array('update.theme_update'),
    );
  }

}
