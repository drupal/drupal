<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\navigation\Menu\NavigationMenuLinkTreeManipulators;
use Drupal\system\Controller\SystemController;
use Drupal\Tests\Core\Menu\MenuLinkMock;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * Tests the navigation menu link tree manipulator.
 *
 * @group navigation
 *
 * @coversDefaultClass \Drupal\navigation\Menu\NavigationMenuLinkTreeManipulators
 */
class NavigationMenuLinkTreeManipulatorsTest extends UnitTestCase {

  /**
   * Tests the addSecondLevelOverviewLinks() tree manipulator.
   *
   * @covers ::addSecondLevelOverviewLinks
   */
  public function testAddSecondLevelOverviewLinks(): void {
    $routeProvider = $this->createMock(RouteProviderInterface::class);
    // For only the route named 'child_list', return a route object with the
    // SystemController::systemAdminMenuBlockPage as the controller.
    $childListRoute = new Route('/test-child-list', ['_controller' => SystemController::class . '::systemAdminMenuBlockPage']);
    $routeProvider->expects($this->any())
      ->method('getRouteByName')
      ->willReturnCallback(static fn ($name) => $name === 'child_list' ? $childListRoute : new Route("/$name"));
    $overrides = $this->createMock(StaticMenuLinkOverridesInterface::class);
    $translation = $this->createMock(TranslationInterface::class);
    $translation
      ->method('translateString')
      ->willReturnCallback(static fn ($string) => $string);
    $manipulator = new NavigationMenuLinkTreeManipulators($routeProvider, $overrides, $translation);

    $originalTree = $this->mockTree();
    // Make sure overview links do not already exist.
    $this->assertArrayNotHasKey('test.example3.navigation_overview', $originalTree[2]->subtree[3]->subtree);
    $this->assertArrayNotHasKey('test.example6.navigation_overview', $originalTree[5]->subtree[6]->subtree);
    $tree = $manipulator->addSecondLevelOverviewLinks($originalTree);

    // First level menu items should not have any children added.
    $this->assertEmpty($tree[1]->subtree);
    $this->assertEquals($originalTree[2]->subtree, $tree[2]->subtree);
    $this->assertEquals($originalTree[5]->subtree, $tree[5]->subtree);
    $this->assertEquals($originalTree[8]->subtree, $tree[8]->subtree);
    $this->assertEquals($originalTree[11]->subtree, $tree[11]->subtree);
    $this->assertEquals($originalTree[13]->subtree, $tree[13]->subtree);
    $this->assertEquals($originalTree[16]->subtree, $tree[16]->subtree);
    $this->assertEquals($originalTree[19]->subtree, $tree[19]->subtree);

    // Leaves should not have any children added.
    $this->assertEmpty($tree[2]->subtree[3]->subtree[4]->subtree);
    $this->assertEmpty($tree[5]->subtree[6]->subtree[7]->subtree);
    $this->assertEmpty($tree[8]->subtree[9]->subtree[10]->subtree);
    $this->assertEmpty($tree[11]->subtree[12]->subtree);
    $this->assertEmpty($tree[13]->subtree[14]->subtree[15]->subtree);
    $this->assertEmpty($tree[16]->subtree[17]->subtree[18]->subtree);
    $this->assertEmpty($tree[19]->subtree[20]->subtree[21]->subtree);
    $this->assertEmpty($tree[19]->subtree[20]->subtree[22]->subtree);

    // Links 3 and 6 should have overview children, even though 6 is unrouted.
    $this->assertArrayHasKey('test.example3.navigation_overview', $tree[2]->subtree[3]->subtree);
    $this->assertArrayHasKey('test.example6.navigation_overview', $tree[5]->subtree[6]->subtree);

    // Link 9 is a child list page, so it should not have an overview child.
    $this->assertArrayNotHasKey('test.example9.navigation_overview', $tree[8]->subtree[9]->subtree);

    // Link 14 and Link 17 are <nolink> and <button> routes, so they should not
    // have overview children.
    $this->assertArrayNotHasKey('test.example14.navigation_overview', $tree[13]->subtree[14]->subtree);
    $this->assertArrayNotHasKey('test.example17.navigation_overview', $tree[16]->subtree[17]->subtree);

    // Link 20's child links are either inaccessible, disabled, or link to the
    // same route as 20, so it should not have an overview child.
    $this->assertArrayNotHasKey('test.example20.navigation_overview', $tree[19]->subtree[20]->subtree);
  }

  /**
   * Creates a mock tree.
   *
   * This mocks a tree with the following structure:
   * - 1
   * - 2
   *   - 3
   *     - 4
   * - 5
   *   - 6 (external)
   *     - 7
   * - 8
   *   - 9
   *     - 10
   * - 11
   *   - 12
   * - 13
   *   - 14 (nolink)
   *     - 15
   * - 16
   *   - 17 (button)
   *     - 18
   * - 19
   *   - 20
   *    - 21 (disabled)
   *    - 22 (access denied)
   *    - 23 (links to same routed URL as 20)
   *
   * With link 9 linking to a page that contains a list of child menu links.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The mock menu tree.
   */
  protected function mockTree(): array {
    $links = [
      1 => MenuLinkMock::create([
        'id' => 'test.example1',
        'route_name' => 'example1',
        'title' => 'foo',
        'parent' => '',
      ]),
      2 => MenuLinkMock::create([
        'id' => 'test.example2',
        'route_name' => 'example2',
        'title' => 'foo',
        'parent' => '',
      ]),
      3 => MenuLinkMock::create([
        'id' => 'test.example3',
        'route_name' => 'example3',
        'title' => 'baz',
        'parent' => 'test.example2',
      ]),
      4 => MenuLinkMock::create([
        'id' => 'test.example4',
        'route_name' => 'example4',
        'title' => 'qux',
        'parent' => 'test.example3',
      ]),
      5 => MenuLinkMock::create([
        'id' => 'test.example5',
        'route_name' => 'example5',
        'title' => 'title5',
        'parent' => '',
      ]),
      6 => MenuLinkMock::create([
        'id' => 'test.example6',
        'route_name' => '',
        'url' => 'https://www.drupal.org/',
        'title' => 'bar_bar',
        'parent' => 'test.example5',
      ]),
      7 => MenuLinkMock::create([
        'id' => 'test.example7',
        'route_name' => 'example7',
        'title' => 'title7',
        'parent' => 'test.example6',
      ]),
      8 => MenuLinkMock::create([
        'id' => 'test.example8',
        'route_name' => 'example8',
        'title' => 'title8',
        'parent' => '',
      ]),
      9 => MenuLinkMock::create([
        'id' => 'test.example9',
        'route_name' => 'child_list',
        'title' => 'title9',
        'parent' => 'test.example8',
      ]),
      10 => MenuLinkMock::create([
        'id' => 'test.example10',
        'route_name' => 'example9',
        'title' => 'title10',
        'parent' => 'test.example9',
      ]),
      11 => MenuLinkMock::create([
        'id' => 'test.example11',
        'route_name' => 'example11',
        'title' => 'title11',
        'parent' => '',
      ]),
      12 => MenuLinkMock::create([
        'id' => 'test.example12',
        'route_name' => 'example12',
        'title' => 'title12',
        'parent' => 'text.example11',
      ]),
      13 => MenuLinkMock::create([
        'id' => 'test.example13',
        'route_name' => 'example13',
        'title' => 'title13',
        'parent' => '',
      ]),
      14 => MenuLinkMock::create([
        'id' => 'test.example14',
        'route_name' => '<nolink>',
        'title' => 'title14',
        'parent' => 'text.example13',
      ]),
      15 => MenuLinkMock::create([
        'id' => 'test.example15',
        'route_name' => 'example15',
        'title' => 'title15',
        'parent' => 'text.example14',
      ]),
      16 => MenuLinkMock::create([
        'id' => 'test.example16',
        'route_name' => 'example16',
        'title' => 'title16',
        'parent' => '',
      ]),
      17 => MenuLinkMock::create([
        'id' => 'test.example17',
        'route_name' => '<button>',
        'title' => 'title17',
        'parent' => 'text.example16',
      ]),
      18 => MenuLinkMock::create([
        'id' => 'test.example18',
        'route_name' => 'example18',
        'title' => 'title18',
        'parent' => 'text.example17',
      ]),
      19 => MenuLinkMock::create([
        'id' => 'test.example19',
        'route_name' => 'example19',
        'title' => 'title19',
        'parent' => '',
      ]),
      20 => MenuLinkMock::create([
        'id' => 'test.example20',
        'route_name' => 'example20',
        'title' => 'title20',
        'parent' => 'test.example19',
      ]),
      21 => MenuLinkMock::create([
        'id' => 'test.example21',
        'route_name' => 'example21',
        'title' => 'title21',
        'parent' => 'test.example20',
        'enabled' => FALSE,
      ]),
      22 => MenuLinkMock::create([
        'id' => 'test.example22',
        'route_name' => 'no_access',
        'title' => 'title22',
        'parent' => 'test.example20',
      ]),
      23 => MenuLinkMock::create([
        'id' => 'test.example23',
        'route_name' => 'example20',
        'title' => 'title23',
        'parent' => 'test.example20',
      ]),
    ];
    $tree = [];
    $tree[1] = new MenuLinkTreeElement($links[1], FALSE, 1, FALSE, []);
    $tree[2] = new MenuLinkTreeElement($links[2], TRUE, 1, FALSE, [
      3 => new MenuLinkTreeElement($links[3], TRUE, 2, FALSE, [
        4 => new MenuLinkTreeElement($links[4], FALSE, 3, FALSE, []),
      ]),
    ]);
    $tree[5] = new MenuLinkTreeElement($links[5], TRUE, 1, FALSE, [
      6 => new MenuLinkTreeElement($links[6], TRUE, 2, FALSE, [
        7 => new MenuLinkTreeElement($links[7], FALSE, 3, FALSE, []),
      ]),
    ]);
    $tree[8] = new MenuLinkTreeElement($links[8], TRUE, 1, FALSE, [
      9 => new MenuLinkTreeElement($links[9], TRUE, 2, FALSE, [
        10 => new MenuLinkTreeElement($links[10], FALSE, 3, FALSE, []),
      ]),
    ]);
    $tree[11] = new MenuLinkTreeElement($links[11], TRUE, 1, FALSE, [
      12 => new MenuLinkTreeElement($links[12], FALSE, 2, FALSE, []),
    ]);
    $tree[13] = new MenuLinkTreeElement($links[13], TRUE, 1, FALSE, [
      14 => new MenuLinkTreeElement($links[14], TRUE, 2, FALSE, [
        15 => new MenuLinkTreeElement($links[15], FALSE, 3, FALSE, []),
      ]),
    ]);
    $tree[16] = new MenuLinkTreeElement($links[16], TRUE, 1, FALSE, [
      17 => new MenuLinkTreeElement($links[17], TRUE, 2, FALSE, [
        18 => new MenuLinkTreeElement($links[18], FALSE, 3, FALSE, []),
      ]),
    ]);
    $tree[19] = new MenuLinkTreeElement($links[19], TRUE, 1, FALSE, [
      20 => new MenuLinkTreeElement($links[20], TRUE, 2, FALSE, [
        21 => new MenuLinkTreeElement($links[21], FALSE, 3, FALSE, []),
        22 => new MenuLinkTreeElement($links[22], FALSE, 3, FALSE, []),
        23 => new MenuLinkTreeElement($links[23], FALSE, 3, FALSE, []),
      ]),
    ]);
    $tree[19]->subtree[20]->subtree[22]->access = AccessResult::forbidden();

    return $tree;
  }

}
