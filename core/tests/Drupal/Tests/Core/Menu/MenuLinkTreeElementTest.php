<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Menu\MenuLinkTreeElementTest.
 */

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the menu link tree element value object.
 *
 * @group Drupal
 * @group Menu
 *
 * @coversDefaultClass \Drupal\Core\Menu\MenuLinkTreeElement
 */
class MenuLinkTreeElementTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Tests \Drupal\Core\Menu\MenuLinkTreeElement',
      'description' => '',
      'group' => 'Menu',
    );
  }

  /**
   * Tests construction.
   *
   * @covers ::__construct
   */
  public function testConstruction() {
    $link = array();
    $item = new MenuLinkTreeElement($link, FALSE, 3, FALSE, array());
    $this->assertSame($link, $item->link);
    $this->assertSame(FALSE, $item->hasChildren);
    $this->assertSame(3, $item->depth);
    $this->assertSame(FALSE, $item->inActiveTrail);
    $this->assertSame(array(), $item->subtree);
  }

  /**
   * Tests count().
   *
   * @covers ::count
   */
  public function testCount() {
    $link_1 = array();
    $link_2 = array();
    $child_item = new MenuLinkTreeElement($link_2, FALSE, 2, FALSE, array());
    $parent_item = new MenuLinkTreeElement($link_1, FALSE, 2, FALSE, array($child_item));
    $this->assertSame(1, $child_item->count());
    $this->assertSame(2, $parent_item->count());
  }

}
