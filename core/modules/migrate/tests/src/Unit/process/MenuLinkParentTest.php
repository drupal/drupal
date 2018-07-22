<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
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
    $this->setExpectedException(MigrateSkipRowException::class, "No parent link found for plid '1' in menu 'admin'.");
    $this->plugin->transform([1, 'admin', NULL], $this->migrateExecutable, $this->row, 'destinationproperty');
  }

}
