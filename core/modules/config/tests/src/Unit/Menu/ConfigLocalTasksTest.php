<?php

namespace Drupal\Tests\config\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;

/**
 * Tests existence of config local tasks.
 *
 * @group config
 */
class ConfigLocalTasksTest extends LocalTaskIntegrationTestBase {

  protected function setUp(): void {
    $this->directoryList = ['config' => 'core/modules/config'];
    parent::setUp();
  }

  /**
   * Tests config local tasks existence.
   *
   * @dataProvider getConfigAdminRoutes
   */
  public function testConfigAdminLocalTasks($route, $expected) {
    $this->assertLocalTasks($route, $expected);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getConfigAdminRoutes() {
    return [
      ['config.sync', [['config.sync', 'config.import', 'config.export']]],
      ['config.import_full', [['config.sync', 'config.import', 'config.export'], ['config.import_full', 'config.import_single']]],
      ['config.import_single', [['config.sync', 'config.import', 'config.export'], ['config.import_full', 'config.import_single']]],
      ['config.export_full', [['config.sync', 'config.import', 'config.export'], ['config.export_full', 'config.export_single']]],
      ['config.export_single', [['config.sync', 'config.import', 'config.export'], ['config.export_full', 'config.export_single']]],
    ];
  }

}
