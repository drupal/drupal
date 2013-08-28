<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Menu\TreeDataUnitTest.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\menu_link\Entity\MenuLink;
use Drupal\simpletest\UnitTestBase;

/**
 * Menu tree data related tests.
 */
class TreeDataUnitTest extends UnitTestBase {
  /**
   * Dummy link structure acceptable for menu_tree_data().
   */
  protected $links = array();

  public static function getInfo() {
    return array(
      'name' => 'Menu tree generation',
      'description' => 'Tests recursive menu tree generation functions.',
      'group' => 'Menu',
    );
  }

  /**
   * Validate the generation of a proper menu tree hierarchy.
   */
  public function testMenuTreeData() {
    $this->links = array(
      1 => new MenuLink(array('mlid' => 1, 'depth' => 1), 'menu_link'),
      2 => new MenuLink(array('mlid' => 2, 'depth' => 1), 'menu_link'),
      3 => new MenuLink(array('mlid' => 3, 'depth' => 2), 'menu_link'),
      4 => new MenuLink(array('mlid' => 4, 'depth' => 3), 'menu_link'),
      5 => new MenuLink(array('mlid' => 5, 'depth' => 1), 'menu_link'),
    );

    $tree = menu_tree_data($this->links);

    // Validate that parent items #1, #2, and #5 exist on the root level.
    $this->assertSameLink($this->links[1], $tree[1]['link'], 'Parent item #1 exists.');
    $this->assertSameLink($this->links[2], $tree[2]['link'], 'Parent item #2 exists.');
    $this->assertSameLink($this->links[5], $tree[5]['link'], 'Parent item #5 exists.');

    // Validate that child item #4 exists at the correct location in the hierarchy.
    $this->assertSameLink($this->links[4], $tree[2]['below'][3]['below'][4]['link'], 'Child item #4 exists in the hierarchy.');
  }

  /**
   * Check that two menu links are the same by comparing the mlid.
   *
   * @param $link1
   *   A menu link item.
   * @param $link2
   *   A menu link item.
   * @param $message
   *   The message to display along with the assertion.
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertSameLink($link1, $link2, $message = '') {
    return $this->assert($link1['mlid'] == $link2['mlid'], $message ?: 'First link is identical to second link');
  }
}
