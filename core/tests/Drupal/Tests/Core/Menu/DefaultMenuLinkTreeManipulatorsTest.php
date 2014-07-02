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
 * @group Drupal
 * @group Menu
 *
 * @coversDefaultClass \Drupal\Core\Menu\DefaultMenuLinkTreeManipulators
 */
class DefaultMenuLinkTreeManipulatorsTest extends UnitTestCase {

  /**
   * The mocked access manager.
   *
   * @var \Drupal\Core\Access\AccessManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $accessManager;

  /**
   * The mocked current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $currentUser;

  /**
   * The default menu link tree manipulators.
   *
   * @var \Drupal\Core\Menu\DefaultMenuLinkTreeManipulators
   */
  protected $defaultMenuTreeManipulators;

  /**
   * The original menu tree build in mockTree()
   *
   * @var array
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
  public static function getInfo() {
    return array(
      'name' => 'Tests \Drupal\Core\Menu\DefaultMenuLinkTreeManipulators',
      'description' => '',
      'group' => 'Menu',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->accessManager = $this->getMockBuilder('\Drupal\Core\Access\AccessManager')
      ->disableOriginalConstructor()->getMock();
    $this->currentUser = $this->getMock('Drupal\Core\Session\AccountInterface');

    $this->defaultMenuTreeManipulators = new DefaultMenuLinkTreeManipulators($this->accessManager, $this->currentUser);
  }

  /**
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
      6 => MenuLinkMock::create(array('id' => 'test.example6', 'route_name' => '', 'url' => 'https://drupal.org/', 'title' => 'barbar', 'parent' => '')),
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

    // Validate that child element #4 exists at the correct location in the hierarchy.
    $this->assertEquals($this->links[4]->getPluginId(), $tree['50000 bar test.example2']->subtree['50000 baz test.example3']->subtree['50000 qux test.example4']->link->getPluginId());
    // Validate that child element #7 exists at the correct location in the hierarchy.
    $this->assertEquals($this->links[7]->getPluginId(), $tree['50000 foofoo test.example5']->subtree['50000 bazbaz test.example7']->link->getPluginId());
  }

  /**
   * Tests the checkAccess() tree manipulator.
   *
   * @covers ::checkAccess
   */
  public function testCheckAccess() {
    // Those menu links that are non-external will have their access checks
    // performed. 8 routes, but 1 is external, 2 already have their 'access'
    // property set, and 1 is a child if an inaccessible menu link, so only 4
    // calls will be made.
    $this->accessManager->expects($this->exactly(4))
      ->method('checkNamedRoute')
      ->will($this->returnValueMap(array(
        array('example1', array(), $this->currentUser, NULL, FALSE),
        array('example2', array('foo' => 'bar'), $this->currentUser, NULL, TRUE),
        array('example3', array('baz' => 'qux'), $this->currentUser, NULL, FALSE),
        array('example5', array(), $this->currentUser, NULL, TRUE),
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
   * Tests the extractSubtreeOfActiveTrail() tree manipulator.
   *
   * @covers ::extractSubtreeOfActiveTrail
   */
  public function testExtractSubtreeOfActiveTrail() {
    // No link in the active trail.
    $this->mockTree();
    // Get level 0.
    $tree = $this->defaultMenuTreeManipulators->extractSubtreeOfActiveTrail($this->originalTree, 0);
    $this->assertEquals(array(1, 2, 5, 6, 8), array_keys($tree));
    // Get level 1.
    $tree = $this->defaultMenuTreeManipulators->extractSubtreeOfActiveTrail($this->originalTree, 1);
    $this->assertEquals(array(), array_keys($tree));
    // Get level 2.
    $tree = $this->defaultMenuTreeManipulators->extractSubtreeOfActiveTrail($this->originalTree, 1);
    $this->assertEquals(array(), array_keys($tree));

    // Link 5 in the active trail.
    $this->mockTree();
    $this->originalTree[5]->inActiveTrail = TRUE;
    // Get level 0.
    $tree = $this->defaultMenuTreeManipulators->extractSubtreeOfActiveTrail($this->originalTree, 0);
    $this->assertEquals(array(1, 2, 5, 6, 8), array_keys($tree));
    // Get level 1.
    $tree = $this->defaultMenuTreeManipulators->extractSubtreeOfActiveTrail($this->originalTree, 1);
    $this->assertEquals(array(7), array_keys($tree));
    // Get level 2.
    $tree = $this->defaultMenuTreeManipulators->extractSubtreeOfActiveTrail($this->originalTree, 2);
    $this->assertEquals(array(), array_keys($tree));

    // Link 2 in the active trail.
    $this->mockTree();
    $this->originalTree[2]->inActiveTrail = TRUE;
    // Get level 0.
    $tree = $this->defaultMenuTreeManipulators->extractSubtreeOfActiveTrail($this->originalTree, 0);
    $this->assertEquals(array(1, 2, 5, 6, 8), array_keys($tree));
    // Get level 1.
    $tree = $this->defaultMenuTreeManipulators->extractSubtreeOfActiveTrail($this->originalTree, 1);
    $this->assertEquals(array(3), array_keys($tree));
    // Get level 2.
    $tree = $this->defaultMenuTreeManipulators->extractSubtreeOfActiveTrail($this->originalTree, 2);
    $this->assertEquals(array(), array_keys($tree));

    // Links 2 and 3 in the active trail.
    $this->mockTree();
    $this->originalTree[2]->inActiveTrail = TRUE;
    $this->originalTree[2]->subtree[3]->inActiveTrail = TRUE;
    // Get level 0.
    $tree = $this->defaultMenuTreeManipulators->extractSubtreeOfActiveTrail($this->originalTree, 0);
    $this->assertEquals(array(1, 2, 5, 6, 8), array_keys($tree));
    // Get level 1.
    $tree = $this->defaultMenuTreeManipulators->extractSubtreeOfActiveTrail($this->originalTree, 1);
    $this->assertEquals(array(3), array_keys($tree));
    // Get level 2.
    $tree = $this->defaultMenuTreeManipulators->extractSubtreeOfActiveTrail($this->originalTree, 2);
    $this->assertEquals(array(4), array_keys($tree));
  }

}
