<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Menu\MenuLinkDefault
 * @group Menu
 */
class MenuLinkDefaultTest extends UnitTestCase {

  /**
   * @covers ::updateLink
   */
  public function testUpdateLink(): void {
    $plugin_definition = [
      'title' => 'Hey jude',
      'enabled' => 1,
      'expanded' => 1,
      'menu_name' => 'admin',
      'parent' => '',
      'weight' => 10,
    ];
    $expected_plugin_definition = $plugin_definition;
    $expected_plugin_definition['weight'] = -10;

    $static_override = $this->prophesize(StaticMenuLinkOverridesInterface::class);
    $static_override->saveOverride('example_menu_link', $expected_plugin_definition);
    $static_override = $static_override->reveal();

    $menu_link = new MenuLinkDefault([], 'example_menu_link', $plugin_definition, $static_override);

    $this->assertEquals($expected_plugin_definition, $menu_link->updateLink(['weight' => -10], TRUE));
  }

  /**
   * @covers ::updateLink
   */
  public function testUpdateLinkWithoutPersist(): void {
    $plugin_definition = [
      'title' => 'Hey jude',
      'enabled' => 1,
      'expanded' => 1,
      'menu_name' => 'admin',
      'parent' => '',
      'weight' => 10,
    ];
    $expected_plugin_definition = $plugin_definition;
    $expected_plugin_definition['weight'] = -10;

    $static_override = $this->prophesize(StaticMenuLinkOverridesInterface::class);
    $static_override->saveOverride()->shouldNotBeCalled();
    $static_override = $static_override->reveal();

    $menu_link = new MenuLinkDefault([], 'example_menu_link', $plugin_definition, $static_override);

    $this->assertEquals($expected_plugin_definition, $menu_link->updateLink(['weight' => -10], FALSE));
  }

}
