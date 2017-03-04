<?php

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\DefaultMenuLinkTreeManipulators;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Tests\UnitTestCase;
use Drupal\node\NodeInterface;

/**
 * Tests the default menu link tree manipulators.
 *
 * @group Menu
 *
 * @coversDefaultClass \Drupal\Core\Menu\DefaultMenuLinkTreeManipulators
 */
class DefaultMenuLinkTreeManipulatorsTest extends UnitTestCase {

  /**
   * The mocked access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $accessManager;

  /**
   * The mocked current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $currentUser;

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityTypeManager;

  /**
   * The default menu link tree manipulators.
   *
   * @var \Drupal\Core\Menu\DefaultMenuLinkTreeManipulators
   */
  protected $defaultMenuTreeManipulators;

  /**
   * The original menu tree build in mockTree().
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeElement[]
   */
  protected $originalTree = [];

  /**
   * Array of menu link instances
   *
   * @var \Drupal\Core\Menu\MenuLinkInterface[]
   */
  protected $links = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->accessManager = $this->getMock('\Drupal\Core\Access\AccessManagerInterface');
    $this->currentUser = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->currentUser->method('isAuthenticated')
      ->willReturn(TRUE);
    $this->entityTypeManager = $this->getMock(EntityTypeManagerInterface::class);

    $this->defaultMenuTreeManipulators = new DefaultMenuLinkTreeManipulators($this->accessManager, $this->currentUser, $this->entityTypeManager);

    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens()->willReturn(TRUE);
    $cache_contexts_manager->reveal();

    $container = new Container();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
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
   *   - 7
   * - 6
   * - 8
   * - 9
   *
   * With link 6 being the only external link.
   */
  protected function mockTree() {
    $this->links = [
      1 => MenuLinkMock::create(['id' => 'test.example1', 'route_name' => 'example1', 'title' => 'foo', 'parent' => '']),
      2 => MenuLinkMock::create(['id' => 'test.example2', 'route_name' => 'example2', 'title' => 'bar', 'parent' => 'test.example1', 'route_parameters' => ['foo' => 'bar']]),
      3 => MenuLinkMock::create(['id' => 'test.example3', 'route_name' => 'example3', 'title' => 'baz', 'parent' => 'test.example2', 'route_parameters' => ['baz' => 'qux']]),
      4 => MenuLinkMock::create(['id' => 'test.example4', 'route_name' => 'example4', 'title' => 'qux', 'parent' => 'test.example3']),
      5 => MenuLinkMock::create(['id' => 'test.example5', 'route_name' => 'example5', 'title' => 'foofoo', 'parent' => '']),
      6 => MenuLinkMock::create(['id' => 'test.example6', 'route_name' => '', 'url' => 'https://www.drupal.org/', 'title' => 'barbar', 'parent' => '']),
      7 => MenuLinkMock::create(['id' => 'test.example7', 'route_name' => 'example7', 'title' => 'bazbaz', 'parent' => '']),
      8 => MenuLinkMock::create(['id' => 'test.example8', 'route_name' => 'example8', 'title' => 'quxqux', 'parent' => '']),
      9 => DynamicMenuLinkMock::create(['id' => 'test.example9', 'parent' => ''])->setCurrentUser($this->currentUser),
    ];
    $this->originalTree = [];
    $this->originalTree[1] = new MenuLinkTreeElement($this->links[1], FALSE, 1, FALSE, []);
    $this->originalTree[2] = new MenuLinkTreeElement($this->links[2], TRUE, 1, FALSE, [
      3 => new MenuLinkTreeElement($this->links[3], TRUE, 2, FALSE, [
        4 => new MenuLinkTreeElement($this->links[4], FALSE, 3, FALSE, []),
      ]),
    ]);
    $this->originalTree[5] = new MenuLinkTreeElement($this->links[5], TRUE, 1, FALSE, [
      7 => new MenuLinkTreeElement($this->links[7], FALSE, 2, FALSE, []),
    ]);
    $this->originalTree[6] = new MenuLinkTreeElement($this->links[6], FALSE, 1, FALSE, []);
    $this->originalTree[8] = new MenuLinkTreeElement($this->links[8], FALSE, 1, FALSE, []);
    $this->originalTree[9] = new MenuLinkTreeElement($this->links[9], FALSE, 1, FALSE, []);
  }

  /**
   * Tests the generateIndexAndSort() tree manipulator.
   *
   * @covers ::generateIndexAndSort
   */
  public function testGenerateIndexAndSort() {
    $this->mockTree();
    $tree = $this->originalTree;
    $tree = $this->defaultMenuTreeManipulators->generateIndexAndSort($tree);

    // Validate that parent elements #1, #2, #5 and #6 exist on the root level.
    $this->assertEquals($this->links[1]->getPluginId(), $tree['50000 foo test.example1']->link->getPluginId());
    $this->assertEquals($this->links[2]->getPluginId(), $tree['50000 bar test.example2']->link->getPluginId());
    $this->assertEquals($this->links[5]->getPluginId(), $tree['50000 foofoo test.example5']->link->getPluginId());
    $this->assertEquals($this->links[6]->getPluginId(), $tree['50000 barbar test.example6']->link->getPluginId());
    $this->assertEquals($this->links[8]->getPluginId(), $tree['50000 quxqux test.example8']->link->getPluginId());

    // Verify that child element #4 is at the correct location in the hierarchy.
    $this->assertEquals($this->links[4]->getPluginId(), $tree['50000 bar test.example2']->subtree['50000 baz test.example3']->subtree['50000 qux test.example4']->link->getPluginId());
    // Verify that child element #7 is at the correct location in the hierarchy.
    $this->assertEquals($this->links[7]->getPluginId(), $tree['50000 foofoo test.example5']->subtree['50000 bazbaz test.example7']->link->getPluginId());
  }

  /**
   * Tests the checkAccess() tree manipulator.
   *
   * @covers ::checkAccess
   * @covers ::menuLinkCheckAccess
   */
  public function testCheckAccess() {
    // Those menu links that are non-external will have their access checks
    // performed. 9 routes, but 1 is external, 2 already have their 'access'
    // property set, and 1 is a child if an inaccessible menu link, so only 5
    // calls will be made.
    $this->accessManager->expects($this->exactly(5))
      ->method('checkNamedRoute')
      ->will($this->returnValueMap([
        ['example1', [], $this->currentUser, TRUE, AccessResult::forbidden()],
        ['example2', ['foo' => 'bar'], $this->currentUser, TRUE, AccessResult::allowed()->cachePerPermissions()],
        ['example3', ['baz' => 'qux'], $this->currentUser, TRUE, AccessResult::neutral()],
        ['example5', [], $this->currentUser, TRUE, AccessResult::allowed()],
        ['user.logout', [], $this->currentUser, TRUE, AccessResult::allowed()],
      ]));

    $this->mockTree();
    $this->originalTree[5]->subtree[7]->access = AccessResult::neutral();
    $this->originalTree[8]->access = AccessResult::allowed()->cachePerUser();

    // Since \Drupal\Core\Menu\DefaultMenuLinkTreeManipulators::checkAccess()
    // allows access to any link if the user has the 'link to any page'
    // permission, *every* single access result is varied by permissions.
    $tree = $this->defaultMenuTreeManipulators->checkAccess($this->originalTree);

    // Menu link 1: route without parameters, access forbidden, but at level 0,
    // hence kept.
    $element = $tree[1];
    $this->assertEquals(AccessResult::forbidden()->cachePerPermissions(), $element->access);
    $this->assertInstanceOf('\Drupal\Core\Menu\InaccessibleMenuLink', $element->link);
    // Menu link 2: route with parameters, access granted.
    $element = $tree[2];
    $this->assertEquals(AccessResult::allowed()->cachePerPermissions(), $element->access);
    $this->assertNotInstanceOf('\Drupal\Core\Menu\InaccessibleMenuLink', $element->link);
    // Menu link 3: route with parameters, AccessResult::neutral(), top-level
    // inaccessible link, hence kept for its cacheability metadata.
    // Note that the permissions cache context is added automatically, because
    // we always check the "link to any page" permission.
    $element = $tree[2]->subtree[3];
    $this->assertEquals(AccessResult::neutral()->cachePerPermissions(), $element->access);
    $this->assertInstanceOf('\Drupal\Core\Menu\InaccessibleMenuLink', $element->link);
    // Menu link 4: child of menu link 3, which was AccessResult::neutral(),
    // hence menu link 3's subtree is removed, of which this menu link is one.
    $this->assertFalse(array_key_exists(4, $tree[2]->subtree[3]->subtree));
    // Menu link 5: no route name, treated as external, hence access granted.
    $element = $tree[5];
    $this->assertEquals(AccessResult::allowed()->cachePerPermissions(), $element->access);
    $this->assertNotInstanceOf('\Drupal\Core\Menu\InaccessibleMenuLink', $element->link);
    // Menu link 6: external URL, hence access granted.
    $element = $tree[6];
    $this->assertEquals(AccessResult::allowed()->cachePerPermissions(), $element->access);
    $this->assertNotInstanceOf('\Drupal\Core\Menu\InaccessibleMenuLink', $element->link);
    // Menu link 7: 'access' already set: AccessResult::neutral(), top-level
    // inaccessible link, hence kept for its cacheability metadata.
    // Note that unlike for menu link 3, the permission cache context is absent,
    // because ::checkAccess() doesn't perform access checking when 'access' is
    // already set.
    $element = $tree[5]->subtree[7];
    $this->assertEquals(AccessResult::neutral(), $element->access);
    $this->assertInstanceOf('\Drupal\Core\Menu\InaccessibleMenuLink', $element->link);
    // Menu link 8: 'access' already set, note that 'per permissions' caching
    // is not added.
    $element = $tree[8];
    $this->assertEquals(AccessResult::allowed()->cachePerUser(), $element->access);
    $this->assertNotInstanceOf('\Drupal\Core\Menu\InaccessibleMenuLink', $element->link);
  }

  /**
   * Tests checkAccess() tree manipulator with 'link to any page' permission.
   *
   * @covers ::checkAccess
   * @covers ::menuLinkCheckAccess
   */
  public function testCheckAccessWithLinkToAnyPagePermission() {
    $this->mockTree();
    $this->currentUser->expects($this->exactly(9))
      ->method('hasPermission')
      ->with('link to any page')
      ->willReturn(TRUE);

    $this->mockTree();
    $this->defaultMenuTreeManipulators->checkAccess($this->originalTree);

    $expected_access_result = AccessResult::allowed()->cachePerPermissions();
    $this->assertEquals($expected_access_result, $this->originalTree[1]->access);
    $this->assertEquals($expected_access_result, $this->originalTree[2]->access);
    $this->assertEquals($expected_access_result, $this->originalTree[2]->subtree[3]->access);
    $this->assertEquals($expected_access_result, $this->originalTree[2]->subtree[3]->subtree[4]->access);
    $this->assertEquals($expected_access_result, $this->originalTree[5]->subtree[7]->access);
    $this->assertEquals($expected_access_result, $this->originalTree[6]->access);
    $this->assertEquals($expected_access_result, $this->originalTree[8]->access);
    $this->assertEquals($expected_access_result, $this->originalTree[9]->access);
  }

  /**
   * Tests the flatten() tree manipulator.
   *
   * @covers ::flatten
   */
  public function testFlatten() {
    $this->mockTree();
    $tree = $this->defaultMenuTreeManipulators->flatten($this->originalTree);
    $this->assertEquals([1, 2, 5, 6, 8, 9], array_keys($this->originalTree));
    $this->assertEquals([1, 2, 5, 6, 8, 9, 3, 4, 7], array_keys($tree));
  }

  /**
   * Tests the optimized node access checking.
   *
   * @covers ::checkNodeAccess
   * @covers ::collectNodeLinks
   * @covers ::checkAccess
   */
  public function testCheckNodeAccess() {
    $links = [
      1 => MenuLinkMock::create(['id' => 'node.1', 'route_name' => 'entity.node.canonical', 'title' => 'foo', 'parent' => '', 'route_parameters' => ['node' => 1]]),
      2 => MenuLinkMock::create(['id' => 'node.2', 'route_name' => 'entity.node.canonical', 'title' => 'bar', 'parent' => '', 'route_parameters' => ['node' => 2]]),
      3 => MenuLinkMock::create(['id' => 'node.3', 'route_name' => 'entity.node.canonical', 'title' => 'baz', 'parent' => 'node.2', 'route_parameters' => ['node' => 3]]),
      4 => MenuLinkMock::create(['id' => 'node.4', 'route_name' => 'entity.node.canonical', 'title' => 'qux', 'parent' => 'node.3', 'route_parameters' => ['node' => 4]]),
      5 => MenuLinkMock::create(['id' => 'test.1', 'route_name' => 'test_route', 'title' => 'qux', 'parent' => '']),
      6 => MenuLinkMock::create(['id' => 'test.2', 'route_name' => 'test_route', 'title' => 'qux', 'parent' => 'test.1']),
    ];
    $tree = [];
    $tree[1] = new MenuLinkTreeElement($links[1], FALSE, 1, FALSE, []);
    $tree[2] = new MenuLinkTreeElement($links[2], TRUE, 1, FALSE, [
      3 => new MenuLinkTreeElement($links[3], TRUE, 2, FALSE, [
        4 => new MenuLinkTreeElement($links[4], FALSE, 3, FALSE, []),
      ]),
    ]);
    $tree[5] = new MenuLinkTreeElement($links[5], TRUE, 1, FALSE, [
      6 => new MenuLinkTreeElement($links[6], FALSE, 2, FALSE, []),
    ]);

    $query = $this->getMock('Drupal\Core\Entity\Query\QueryInterface');
    $query->expects($this->at(0))
      ->method('condition')
      ->with('nid', [1, 2, 3, 4]);
    $query->expects($this->at(1))
      ->method('condition')
      ->with('status', NodeInterface::PUBLISHED);
    $query->expects($this->once())
      ->method('execute')
      ->willReturn([1, 2, 4]);
    $storage = $this->getMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('getQuery')
      ->willReturn($query);
    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('node')
      ->willReturn($storage);

    $node_access_result = AccessResult::allowed()->cachePerPermissions()->addCacheContexts(['user.node_grants:view']);

    $tree = $this->defaultMenuTreeManipulators->checkNodeAccess($tree);
    $this->assertEquals($node_access_result, $tree[1]->access);
    $this->assertEquals($node_access_result, $tree[2]->access);
    // Ensure that access denied is set.
    $this->assertEquals(AccessResult::neutral(), $tree[2]->subtree[3]->access);
    $this->assertEquals($node_access_result, $tree[2]->subtree[3]->subtree[4]->access);
    // Ensure that other routes than entity.node.canonical are set as well.
    $this->assertNull($tree[5]->access);
    $this->assertNull($tree[5]->subtree[6]->access);

    // On top of the node access checking now run the ordinary route based
    // access checkers.

    // Ensure that the access manager is just called for the non-node routes.
    $this->accessManager->expects($this->at(0))
      ->method('checkNamedRoute')
      ->with('test_route', [], $this->currentUser, TRUE)
      ->willReturn(AccessResult::allowed());
    $this->accessManager->expects($this->at(1))
      ->method('checkNamedRoute')
      ->with('test_route', [], $this->currentUser, TRUE)
      ->willReturn(AccessResult::neutral());
    $tree = $this->defaultMenuTreeManipulators->checkAccess($tree);

    $this->assertEquals($node_access_result, $tree[1]->access);
    $this->assertEquals($node_access_result, $tree[2]->access);
    $this->assertEquals(AccessResult::neutral(), $tree[2]->subtree[3]->access);
    $this->assertEquals(AccessResult::allowed()->cachePerPermissions(), $tree[5]->access);
    $this->assertEquals(AccessResult::neutral()->cachePerPermissions(), $tree[5]->subtree[6]->access);
  }

}
