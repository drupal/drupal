<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;

/**
 * Tests existence of update local tasks.
 *
 * @group update
 */
class UpdateLocalTasksTest extends LocalTaskIntegrationTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->directoryList = ['update' => 'core/modules/update'];
    parent::setUp();
  }

  /**
   * Checks update report tasks.
   *
   * @dataProvider getUpdateReportRoutes
   */
  public function testUpdateReportLocalTasks($route): void {
    $this->assertLocalTasks($route, [
      0 => ['update.status', 'update.settings', 'update.report_update'],
    ]);
  }

  /**
   * Provides a list of report routes to test.
   */
  public static function getUpdateReportRoutes() {
    return [
      ['update.status'],
      ['update.settings'],
      ['update.report_update'],
    ];
  }

  /**
   * Checks update module tasks.
   *
   * @dataProvider getUpdateModuleRoutes
   */
  public function testUpdateModuleLocalTasks($route): void {
    $this->assertLocalTasks($route, [
      0 => ['update.module_update'],
    ]);
  }

  /**
   * Provides a list of module routes to test.
   */
  public static function getUpdateModuleRoutes() {
    return [
      ['update.module_update'],
    ];
  }

  /**
   * Checks update theme tasks.
   *
   * @dataProvider getUpdateThemeRoutes
   */
  public function testUpdateThemeLocalTasks($route): void {
    $this->assertLocalTasks($route, [
      0 => ['update.theme_update'],
    ]);
  }

  /**
   * Provides a list of theme routes to test.
   */
  public static function getUpdateThemeRoutes() {
    return [
      ['update.theme_update'],
    ];
  }

}
