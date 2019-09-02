<?php

namespace Drupal\Tests\system\Unit\Menu;

use Drupal\Core\Extension\Extension;
use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;

/**
 * Tests existence of system local tasks.
 *
 * @group system
 */
class SystemLocalTasksTest extends LocalTaskIntegrationTestBase {

  /**
   * The mocked theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->directoryList = [
      'system' => 'core/modules/system',
    ];

    $this->themeHandler = $this->createMock('Drupal\Core\Extension\ThemeHandlerInterface');

    $theme = new Extension($this->root, 'theme', '/core/themes/bartik', 'bartik.info.yml');
    $theme->status = 1;
    $theme->info = ['name' => 'bartik'];
    $this->themeHandler->expects($this->any())
      ->method('listInfo')
      ->will($this->returnValue([
        'bartik' => $theme,
      ]));
    $this->themeHandler->expects($this->any())
      ->method('hasUi')
      ->with('bartik')
      ->willReturn(TRUE);
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
    return [
      ['system.admin_content', [['system.admin_content']]],
      [
        'system.theme_settings_theme',
        [
          ['system.themes_page', 'system.theme_settings'],
          ['system.theme_settings_global', 'system.theme_settings_theme:bartik'],
        ],
      ],
    ];
  }

}
