<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Menu\DefaultMenuLinkTreeManipulatorsTest.
 */

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Menu\DefaultMenuLinkTreeManipulators;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Tests\UnitTestCase;

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
   * The mocked query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $queryFactory;

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
  protected $originalTree = array();

  /**
   * Array of menu link instances
   *
   * @var \Drupal\Core\Menu\MenuLinkInterface[]
   */
  protected $links = array();

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->accessManager = $this->getMock('\Drupal\Core\Access\AccessManagerInterface');
    $this->currentUser = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->queryFactory = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();

    $this->defaultMenuTreeManipulators = new DefaultMenuLinkTreeManipulators($this->accessManager, $this->currentUser, $this->queryFactory);
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
   *
   * With link 6 being the only external link.
   */
  protected function mockTree() {
    $this->links = array(
      1 => MenuLinkMock::create(array('id' => 'test.example1', 'route_name' => 'example1', 'title' => 'foo', 'parent' => '')),
      2 => MenuLinkMock::create(array('id' => 'test.example2', 'route_name' => 'example2', 'title' => 'bar', 'parent' => 'test.example1', 'route_parameters' => array('foo' => 'bar'))),
      3 => MenuLinkMock::create(array('id' => 'test.example3', 'route_name' => 'example3', 'title' => 'baz', 'parent' => 'test.example2', 'route_parameters' => array('baz' => 'qux'))),
      4 => MenuLinkMock::create(array('id' => 'test.example4', 'route_name' => 'example4', 'title' => 'qux', 'parent' => 'test.example3')),
      5 => MenuLinkMock::create(array('id' => 'test.example5', 'route_name' => 'example5', 'title' => 'foofoo', 'parent' => '')),
      6 => MenuLinkMock::create(array('id' => 'test.example6', 'route_name' => '', 'url' => 'https://www.drupal.org/', 'title' => 'barbar', 'parent' => '')),
      7 => MenuLinkMock::create(array('id' => 'test.example7', 'route_name' => 'example7', 'title' => 'bazbaz', 'parent' => '')),
      8 => MenuLinkMock::create(array('id' => 'test.example8', 'route_name' => 'example8', 'title' => 'quxqux', 'parent' => '')),
    );
    $this->originalTree = array();
    $this->originalTree[1] = new MenuLinkTreeElement($this->links[1], FALSE, 1, FALSE, array());
    $this->originalTree[2] = new MenuLinkTreeElement($this->links[2], TRUE, 1, FALSE, array(
      3 => new MenuLinkTreeElement($this->links[3], TRUE, 2, FALSE, array(
        4 => new MenuLinkTreeElement($this->links[4], FALSE, 3, FALSE, array()),
      )),
    ));
    $this->originalTree[5] = new MenuLinkTreeElement($this->links[5], TRUE, 1, FALSE, array(
      7 => new MenuLinkTreeElement($this->links[7], FALSE, 2, FALSE, array()),
    ));
    $this->originalTree[6] = new MenuLinkTreeElement($this->links[6], FALSE, 1, FALSE, array());
    $this->originalTree[8] = new MenuLinkTreeElement($this->links[8], FALSE, 1, FALSE, array());
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
    // performed. 8 routes, but 1 is external, 2 already have their 'access'
    // property set, and 1 is a child if an inaccessible menu link, so only 4
    // calls will be made.
    $this->accessManager->expects($this->exactly(4))
      ->method('checkNamedRoute')
      ->will($this->returnValueMap(array(
        array('example1', array(), $this->currentUser,  FALSE, FALSE),
        array('example2', array('foo' => 'bar'), $this->currentUser, FALSE, TRUE),
        array('example3', array('baz' => 'qux'), $this->currentUser, FALSE, FALSE),
        array('example5', array(), $this->currentUser, FALSE, TRUE),
      )));

    $this->mockTree();
    $this->originalTree[5]->subtree[7]->access = TRUE;
    $this->originalTree[8]->access = FALSE;

    $tree = $this->defaultMenuTreeManipulators->checkAccess($this->originalTree);

    // Menu link 1: route without parameters, access forbidden, hence removed.
    $this->assertFalse(array_key_exists(1, $tree));
    // Menu link 2: route with parameters, access granted.
    $element = $tree[2];
    $this->assertTrue($element->access);
    // Menu link 3: route with parameters, access forbidden, hence removed,
    // including its children.
    $this->assertFalse(array_key_exists(3, $tree[2]->subtree));
    // Menu link 4: child of menu link 3, which already is removed.
    $this->assertSame(array(), $tree[2]->subtree);
    // Menu link 5: no route name, treated as external, hence access granted.
    $element = $tree[5];
    $this->assertTrue($element->access);
    // Menu link 6: external URL, hence access granted.
    $element = $tree[6];
    $this->assertTrue($element->access);
    // Menu link 7: 'access' already set.
    $element = $tree[5]->subtree[7];
    $this->assertTrue($element->access);
    // Menu link 8: 'access' already set, to FALSE, hence removed.
    $this->assertFalse(array_key_exists(8, $tree));
  }

  /**
   * Tests checkAccess() tree manipulator with 'link to any page' permission.
   *
   * @covers ::checkAccess
   * @covers ::menuLinkCheckAccess
   */
  public function testCheckAccessWithLinkToAnyPagePermission() {
    $this->mockTree();
    $this->currentUser->expects($this->exactly(8))
      ->method('hasPermission')
      ->with('link to any page')
      ->willReturn(TRUE);

    $this->mockTree();
    $this->defaultMenuTreeManipulators->checkAccess($this->originalTree);

    $this->assertTrue($this->originalTree[1]->access);
    $this->assertTrue($this->originalTree[2]->access);
    $this->assertTrue($this->originalTree[2]->subtree[3]->access);
    $this->assertTrue($this->originalTree[2]->subtree[3]->subtree[4]->access);
    $this->assertTrue($this->originalTree[5]->subtree[7]->access);
    $this->assertTrue($this->originalTree[6]->access);
    $this->assertTrue($this->originalTree[8]->access);
  }

  /**
   * Tests the flatten() tree manipulator.
   *
   * @covers ::flatten
   */
  public function testFlatten() {
    $this->mockTree();
    $tree = $this->defaultMenuTreeManipulators->flatten($this->originalTree);
    $this->assertEquals(array(1, 2, 5, 6, 8), array_keys($this->originalTree));
    $this->assertEquals(array(1, 2, 5, 6, 8, 3, 4, 7), array_keys($tree));
  }

  /**
   * Tests the optimized node access checking.
   *
   * @covers ::checkNodeAccess
   * @covers ::collectNodeLinks
   * @covers ::checkAccess
   */
  public function  testCheckNodeAccess() {
    $links = array(
      1 => MenuLinkMock::create(array('id' => 'node.1', 'route_name' => 'entity.node.canonical', 'title' => 'foo', 'parent' => '', 'route_parameters' => array('node' => 1))),
      2 => MenuLinkMock::create(array('id' => 'node.2', 'route_name' => 'entity.node.canonical', 'title' => 'bar', 'parent' => '', 'route_parameters' => array('node' => 2))),
      3 => MenuLinkMock::create(array('id' => 'node.3', 'route_name' => 'entity.node.canonical', 'title' => 'baz', 'parent' => 'node.2', 'route_parameters' => array('node' => 3))),
      4 => MenuLinkMock::create(array('id' => 'node.4', 'route_name' => 'entity.node.canonical', 'title' => 'qux', 'parent' => 'node.3', 'route_parameters' => array('node' => 4))),
      5 => MenuLinkMock::create(array('id' => 'test.1', 'route_name' => 'test_route', 'title' => 'qux', 'parent' => '')),
      6 => MenuLinkMock::create(array('id' => 'test.2', 'route_name' => 'test_route', 'title' => 'qux', 'parent' => 'test.1')),
    );
    $tree = array();
    $tree[1] = new MenuLinkTreeElement($links[1], FALSE, 1, FALSE, array());
    $tree[2] = new MenuLinkTreeElement($links[2], TRUE, 1, FALSE, array(
      3 => new MenuLinkTreeElement($links[3], TRUE, 2, FALSE, array(
        4 => new MenuLinkTreeElement($links[4], FALSE, 3, FALSE, array()),
      )),
    ));
    $tree[5] = new MenuLinkTreeElement($links[5], TRUE, 1, FALSE, array(
      6 => new MenuLinkTreeElement($links[6], FALSE, 2, FALSE, array()),
    ));

    $query = $this->getMock('Drupal\Core\Entity\Query\QueryInterface');
    $query->expects($this->at(0))
      ->method('condition')
      ->with('nid', array(1, 2, 3, 4));
    $query->expects($this->at(1))
      ->method('condition')
      ->with('status', NODE_PUBLISHED);
    $query->expects($this->once())
      ->method('execute')
      ->willReturn(array(1, 2, 4));
    $this->queryFactory->expects($this->once())
      ->method('get')
      ->with('node')
      ->willReturn($query);

    $tree = $this->defaultMenuTreeManipulators->checkNodeAccess($tree);
    $this->assertTrue($tree[1]->access);
    $this->assertTrue($tree[2]->access);
    // Ensure that access denied is set.
    $this->assertFalse($tree[2]->subtree[3]->access);
    $this->assertTrue($tree[2]->subtree[3]->subtree[4]->access);
    // Ensure that other routes than entity.node.canonical are set as well.
    $this->assertNull($tree[5]->access);
    $this->assertNull($tree[5]->subtree[6]->access);

    // On top of the node access checking now run the ordinary route based
    // access checkers.

    // Ensure that the access manager is just called for the non-node routes.
    $this->accessManager->expects($this->at(0))
      ->method('checkNamedRoute')
      ->with('test_route', [], $this->currentUser)
      ->willReturn(TRUE);
    $this->accessManager->expects($this->at(1))
      ->method('checkNamedRoute')
      ->with('test_route', [], $this->currentUser)
      ->willReturn(FALSE);
    $tree = $this->defaultMenuTreeManipulators->checkAccess($tree);

    $this->assertTrue($tree[1]->access);
    $this->assertTrue($tree[2]->access);
    $this->assertFalse(isset($tree[2]->subtree[3]));
    $this->assertTrue($tree[5]->access);
    $this->assertFalse(isset($tree[5]->subtree[6]));
  }

}

if (!defined('NODE_PUBLISHED')) {
  define('NODE_PUBLISHED', 1);
}
