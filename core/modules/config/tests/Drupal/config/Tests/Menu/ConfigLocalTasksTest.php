<?php

/**
 * @file
 * Contains \Drupal\config\Tests\Menu\ConfigLocalTasksTest.
 */

namespace Drupal\config\Tests\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;

/**
 * Tests existence of config local tasks.
 *
 * @group Drupal
 * @group config
 */
class ConfigLocalTasksTest extends LocalTaskIntegrationTest {

  public static function getInfo() {
    return array(
      'name' => 'Config local tasks test',
      'description' => 'Test existence of config local tasks.',
      'group' => 'config',
    );
  }

  public function setUp() {
    $this->moduleList = array('config' => 'core/modules/config/config.module');
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
    return array(
      array('config.sync', array(array('config.sync', 'config.export', 'config.import'))),
      array('config.export', array(array('config.sync', 'config.export', 'config.import'))),
      array('config.import', array(array('config.sync', 'config.export', 'config.import'))),
    );
  }

}
