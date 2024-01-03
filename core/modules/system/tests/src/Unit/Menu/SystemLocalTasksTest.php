<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Unit\Menu;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->directoryList = [
      'system' => 'core/modules/system',
    ];

    $this->themeHandler = $this->createMock('Drupal\Core\Extension\ThemeHandlerInterface');

    $theme = new Extension($this->root, 'theme', 'core/themes/olivero', 'olivero.info.yml');
    $theme->status = 1;
    $theme->info = ['name' => 'olivero'];
    $this->themeHandler->expects($this->any())
      ->method('listInfo')
      ->willReturn([
        'olivero' => $theme,
      ]);
    $this->themeHandler->expects($this->any())
      ->method('hasUi')
      ->with('olivero')
      ->willReturn(TRUE);
    $this->container->set('theme_handler', $this->themeHandler);

    $fooEntityDefinition = $this->createMock(EntityTypeInterface::class);
    $fooEntityDefinition
      ->expects($this->once())
      ->method('hasLinkTemplate')
      ->with('version-history')
      ->will($this->returnValue(TRUE));
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->expects($this->any())
      ->method('getDefinitions')
      ->willReturn([
        'foo' => $fooEntityDefinition,
      ]);
    $this->container->set('entity_type.manager', $entityTypeManager);
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
          ['system.theme_settings_global', 'system.theme_settings_theme:olivero'],
        ],
      ],
      [
        'entity.foo.version_history',
        [
          ['entity.version_history:foo.version_history'],
        ],
      ],
    ];
  }

}
