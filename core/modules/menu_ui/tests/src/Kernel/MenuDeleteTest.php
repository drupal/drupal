<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_ui\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\menu_ui\Hook\MenuUiHooks;
use Drupal\node\Entity\NodeType;
use Drupal\system\Entity\Menu;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the menu_delete hook.
 */
#[Group('menu_ui')]
#[RunTestsInSeparateProcesses]
class MenuDeleteTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'menu_ui', 'system', 'user'];

  /**
   * Tests menu delete.
   *
   * @legacy-covers \Drupal\menu_ui\Hook\MenuUiHooks::menuDelete
   */
  #[DataProvider('providerMenuDelete')]
  public function testMenuDelete($settings, $expected): void {
    $menu = Menu::create([
      'id' => 'mock',
      'label' => $this->randomMachineName(16),
      'description' => 'Description text',
    ]);
    $menu->save();
    $content_type = NodeType::create([
      'status' => TRUE,
      'dependencies' => [
        'module' => ['menu_ui'],
      ],
      'third_party_settings' => [
        'menu_ui' => $settings,
      ],
      'name' => 'Test type',
      'type' => 'test_type',
    ]);
    $content_type->save();
    $this->assertEquals($settings['available_menus'], $content_type->getThirdPartySetting('menu_ui', 'available_menus'));
    $this->assertEquals($settings['parent'], $content_type->getThirdPartySetting('menu_ui', 'parent'));

    $hooks = new MenuUiHooks(\Drupal::entityTypeManager());
    $hooks->menuDelete($menu);

    $content_type = NodeType::load('test_type');
    $this->assertEquals($expected['available_menus'], $content_type->getThirdPartySetting('menu_ui', 'available_menus'));
    $this->assertEquals($expected['parent'], $content_type->getThirdPartySetting('menu_ui', 'parent'));
  }

  /**
   * Provides data for testMenuDelete().
   */
  public static function providerMenuDelete(): array {
    return [
      [
        ['available_menus' => ['mock'], 'parent' => 'mock:'],
        ['available_menus' => [], 'parent' => ''],
      ],
      [
        ['available_menus' => ['mock'], 'parent' => 'mock:menu_link_content:e0cd7689-016e-43e4-af8f-7ce82801ab95'],
        ['available_menus' => [], 'parent' => ''],
      ],
      [
        ['available_menus' => ['main', 'mock'], 'parent' => 'mock:'],
        ['available_menus' => ['main'], 'parent' => ''],
      ],
      [
        ['available_menus' => ['main'], 'parent' => 'main:'],
        ['available_menus' => ['main'], 'parent' => 'main:'],
      ],
      [
        ['available_menus' => ['main'], 'parent' => 'main:menu_link_content:e0cd7689-016e-43e4-af8f-7ce82801ab95'],
        ['available_menus' => ['main'], 'parent' => 'main:menu_link_content:e0cd7689-016e-43e4-af8f-7ce82801ab95'],
      ],
    ];
  }

}
