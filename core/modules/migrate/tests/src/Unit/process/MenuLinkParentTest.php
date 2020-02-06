<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\migrate\process\MenuLinkParent;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests the menu link parent process plugin.
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\MenuLinkParent
 * @group migrate
 */
class MenuLinkParentTest extends MigrateProcessTestCase {

  /**
   * A MigrationInterface prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $migration;

  /**
   * A MigrateLookupInterface prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $migrateLookup;

  /**
   * A MigrationInterface prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $menuLinkManager;

  /**
   * A MigrationInterface prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $menuLinkStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migration = $this->prophesize(MigrationInterface::class);
    $this->migrateLookup = $this->prophesize(MigrateLookupInterface::class);
    $this->migrateLookup->lookup(NULL, [1])->willReturn([]);
    $this->menuLinkManager = $this->prophesize(MenuLinkManagerInterface::class);
    $this->menuLinkStorage = $this->prophesize(EntityStorageInterface::class);
    $container = new ContainerBuilder();
    $container->set('migrate.lookup', $this->migrateLookup->reveal());
    \Drupal::setContainer($container);

  }

  /**
   * @covers ::transform
   */
  public function testTransformException() {
    $plugin = new MenuLinkParent([], 'map', [], $this->migrateLookup->reveal(), $this->menuLinkManager->reveal(), $this->menuLinkStorage->reveal(), $this->migration->reveal());
    $this->expectException(MigrateSkipRowException::class);
    $this->expectExceptionMessage("No parent link found for plid '1' in menu 'admin'.");
    $plugin->transform([1, 'admin', NULL], $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Tests the plugin when the parent is an external link.
   *
   * @covers ::transform
   */
  public function testTransformExternal() {
    $menu_link_content = $this->prophesize(MenuLinkContentInterface::class);
    $menu_link_content->getPluginId()->willReturn('menu_link_content:fe151460-dfa2-4133-8864-c1746f28ab27');
    $this->menuLinkStorage->loadByProperties([
      'link__uri' => 'http://example.com',
    ])->willReturn([
      9054 => $menu_link_content,
    ]);
    $plugin = $this->prophesize(PluginInspectionInterface::class);
    $this->menuLinkManager->createInstance('menu_link_content:fe151460-dfa2-4133-8864-c1746f28ab27')->willReturn($plugin->reveal());
    $plugin = new MenuLinkParent([], 'map', [], $this->migrateLookup->reveal(), $this->menuLinkManager->reveal(), $this->menuLinkStorage->reveal(), $this->migration->reveal());

    $result = $plugin->transform([1, 'admin', 'http://example.com'], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals('menu_link_content:fe151460-dfa2-4133-8864-c1746f28ab27', $result);
  }

}
