<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\migrate\process\MenuLinkParent;
use Drupal\migrate\Plugin\MigrateProcessInterface;

/**
 * Tests the menu link parent process plugin.
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\MenuLinkParent
 * @group migrate
 */
class MenuLinkParentTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration_plugin = $this->prophesize(MigrateProcessInterface::class);
    $menu_link_manager = $this->prophesize(MenuLinkManagerInterface::class);
    $menu_link_storage = $this->prophesize(EntityStorageInterface::class);
    $this->plugin = new MenuLinkParent([], 'map', [], $migration_plugin->reveal(), $menu_link_manager->reveal(), $menu_link_storage->reveal());
  }

  /**
   * @covers ::transform
   */
  public function testTransformException() {
    $this->expectException(MigrateSkipRowException::class);
    $this->expectExceptionMessage("No parent link found for plid '1' in menu 'admin'.");
    $this->plugin->transform([1, 'admin', NULL], $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Tests the plugin when the parent is an external link.
   *
   * @covers ::transform
   */
  public function testTransformExternal() {
    $migration_plugin = $this->prophesize(MigrateProcessInterface::class);
    $menu_link_manager = $this->prophesize(MenuLinkManagerInterface::class);
    $menu_link_storage = $this->prophesize(EntityStorageInterface::class);
    $menu_link_content = $this->prophesize(MenuLinkContentInterface::class);
    $menu_link_content->getPluginId()->willReturn('menu_link_content:fe151460-dfa2-4133-8864-c1746f28ab27');
    $menu_link_storage->loadByProperties([
      'link__uri' => 'http://example.com',
    ])->willReturn([
      9054 => $menu_link_content,
    ]);
    $plugin = $this->prophesize(PluginInspectionInterface::class);
    $menu_link_manager->createInstance('menu_link_content:fe151460-dfa2-4133-8864-c1746f28ab27')->willReturn($plugin->reveal());
    $plugin = new MenuLinkParent([], 'map', [], $migration_plugin->reveal(), $menu_link_manager->reveal(), $menu_link_storage->reveal());

    $result = $plugin->transform([1, 'admin', 'http://example.com'], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals('menu_link_content:fe151460-dfa2-4133-8864-c1746f28ab27', $result);
  }

}
