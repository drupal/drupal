<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the menu link tree element value object.
 *
 * @group Menu
 *
 * @coversDefaultClass \Drupal\Core\Menu\MenuLinkTreeElement
 */
class MenuLinkTreeElementTest extends UnitTestCase {

  /**
   * Tests construction.
   *
   * @covers ::__construct
   */
  public function testConstruction(): void {
    $link = MenuLinkMock::create(['id' => 'test']);
    $item = new MenuLinkTreeElement($link, FALSE, 3, FALSE, []);
    $this->assertSame($link, $item->link);
    $this->assertFalse($item->hasChildren);
    $this->assertSame(3, $item->depth);
    $this->assertFalse($item->inActiveTrail);
    $this->assertSame([], $item->subtree);
  }

  /**
   * Tests count().
   *
   * @covers ::count
   */
  public function testCount(): void {
    $link_1 = MenuLinkMock::create(['id' => 'test_1']);
    $link_2 = MenuLinkMock::create(['id' => 'test_2']);
    $child_item = new MenuLinkTreeElement($link_2, FALSE, 2, FALSE, []);
    $parent_item = new MenuLinkTreeElement($link_1, FALSE, 2, FALSE, [$child_item]);
    $this->assertSame(1, $child_item->count());
    $this->assertSame(2, $parent_item->count());
  }

}
