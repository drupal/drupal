<?php

/**
 * @file
 * Contains \Drupal\Tests\system\Unit\Menu\SystemLocalTasksTest.
 */

namespace Drupal\Tests\system\Unit\Menu;

use Drupal\Core\Extension\Extension;
use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;

/**
 * Tests existence of system local tasks.
 *
 * @group system
 */
class SystemLocalTasksTest extends LocalTaskIntegrationTest {

  /**
   * The mocked theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->directoryList = array(
      'system' => 'core/modules/system',
    );

    $this->themeHandler = $this->getMock('Drupal\Core\Extension\ThemeHandlerInterface');

    $theme = new Extension($this->root, 'theme', '/core/themes/bartik', 'bartik.info.yml');
    $theme->status = 1;
    $theme->info = array('name' => 'bartik');
    $this->themeHandler->expects($this->any())
      ->method('listInfo')
      ->will($this->returnValue(array(
        'bartik' => $theme,
      )));
    $this->container->set('theme_handler', $this->themeHandler);
  }

  /**
   * Tests local task existence.
   *
   * @dataProvider getSystemAdminRoutes
   */
  public function testSystemAdminLocalTasks($route, $expected) {
    $this->assertLocalTasks($route, $expected);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getSystemAdminRoutes() {
    return array(
      array('system.admin_content', array(array('system.admin_content'))),
      array('system.theme_settings_theme', array(
        array('system.themes_page', 'system.theme_settings'),
        array('system.theme_settings_global', 'system.theme_settings_theme:bartik'),
      )),
    );
  }

}
