<?php

/**
 * @file
 * Contains \Drupal\menu_link\Tests\MenuTreeTest.
 */

namespace Drupal\menu_link\Tests {

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\Language;
use Drupal\menu_link\MenuTree;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

if (!defined('MENU_MAX_DEPTH')) {
  define('MENU_MAX_DEPTH', 9);
}

/**
 * Tests the menu tree.
 *
 * @group Drupal
 * @group menu_link
 *
 * @coversDefaultClass \Drupal\menu_link\MenuTree
 */
class MenuTreeTest extends UnitTestCase {

  /**
   * The tested menu tree.
   *
   * @var \Drupal\menu_link\MenuTree|\Drupal\menu_link\Tests\TestMenuTree
   */
  protected $menuTree;

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $connection;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheBackend;

  /**
   * The mocked language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * The test request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack.
   */
  protected $requestStack;

  /**
   * The mocked entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mocked entity query factor.y
   *
   * @var  \Drupal\Core\Entity\Query\QueryFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityQueryFactory;

  /**
   * The mocked state.
   *
   * @var \Drupal\Core\State\StateInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $state;

  /**
   * Stores some default values for a menu link.
   *
   * @var array
   */
  protected $defaultMenuLink = array(
    'menu_name' => 'main-menu',
    'mlid' => 1,
    'title' => 'Example 1',
    'route_name' => 'example1',
    'link_path' => 'example1',
    'access' => 1,
    'hidden' => FALSE,
    'has_children' => FALSE,
    'in_active_trail' => TRUE,
    'localized_options' => array('attributes' => array('title' => '')),
    'weight' => 0,
  );

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Tests \Drupal\menu_link\MenuTree',
      'description' => '',
      'group' => 'Menu',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();
    $this->cacheBackend = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->languageManager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->requestStack = new RequestStack();
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->entityQueryFactory = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $this->state = $this->getMock('Drupal\Core\State\StateInterface');

    $this->menuTree = new TestMenuTree($this->connection, $this->cacheBackend, $this->languageManager, $this->requestStack, $this->entityManager, $this->entityQueryFactory, $this->state);
  }

  /**
   * Tests active paths.
   *
   * @covers ::setPath
   * @covers ::getPath
   */
  public function testActivePaths() {
    $this->assertNull($this->menuTree->getPath('test_menu1'));

    $this->menuTree->setPath('test_menu1', 'example_path1');
    $this->assertEquals('example_path1', $this->menuTree->getPath('test_menu1'));
    $this->assertNull($this->menuTree->getPath('test_menu2'));

    $this->menuTree->setPath('test_menu2', 'example_path2');
    $this->assertEquals('example_path1', $this->menuTree->getPath('test_menu1'));
    $this->assertEquals('example_path2', $this->menuTree->getPath('test_menu2'));
  }

  /**
   * Tests buildTreeData with a single level.
   *
   * @covers ::buildTreeData
   * @covers ::doBuildTreeData
   */
  public function testBuildTreeDataWithSingleLevel() {
    $items = array();
    $items[] = array(
      'mlid' => 1,
      'depth' => 1,
      'weight' => 0,
      'title' => '',
      'route_name' => 'example1',
      'access' => TRUE,
    );
    $items[] = array(
      'mlid' => 2,
      'depth' => 1,
      'weight' => 0,
      'title' => '',
      'route_name' => 'example2',
      'access' => TRUE,
    );

    $result = $this->menuTree->buildTreeData($items, array(), 1);

    $this->assertCount(2, $result);
    $result1 = array_shift($result);
    $this->assertEquals($items[0] + array('in_active_trail' => FALSE), $result1['link']);
    $result2 = array_shift($result);
    $this->assertEquals($items[1] + array('in_active_trail' => FALSE), $result2['link']);
  }

  /**
   * Tests buildTreeData with a single level and one item being active.
   *
   * @covers ::buildTreeData
   * @covers ::doBuildTreeData
   */
  public function testBuildTreeDataWithSingleLevelAndActiveItem() {
    $items = array();
    $items[] = array(
      'mlid' => 1,
      'depth' => 1,
      'weight' => 0,
      'title' => '',
      'route_name' => 'example1',
      'access' => TRUE,
    );
    $items[] = array(
      'mlid' => 2,
      'depth' => 1,
      'weight' => 0,
      'title' => '',
      'route_name' => 'example2',
      'access' => TRUE,
    );

    $result = $this->menuTree->buildTreeData($items, array(1), 1);

    $this->assertCount(2, $result);
    $result1 = array_shift($result);
    $this->assertEquals($items[0] + array('in_active_trail' => TRUE), $result1['link']);
    $result2 = array_shift($result);
    $this->assertEquals($items[1] + array('in_active_trail' => FALSE), $result2['link']);
  }

  /**
   * Tests buildTreeData with a single level and none item being active.
   *
   * @covers ::buildTreeData
   * @covers ::doBuildTreeData
   */
  public function testBuildTreeDataWithSingleLevelAndNoActiveItem() {
    $items = array();
    $items[] = array(
      'mlid' => 1,
      'depth' => 1,
      'weight' => 0,
      'title' => '',
      'route_name' => 'example1',
      'access' => TRUE,
    );
    $items[] = array(
      'mlid' => 2,
      'depth' => 1,
      'weight' => 0,
      'title' => '',
      'route_name' => 'example2',
      'access' => TRUE,
    );

    $result = $this->menuTree->buildTreeData($items, array(3), 1);

    $this->assertCount(2, $result);
    $result1 = array_shift($result);
    $this->assertEquals($items[0] + array('in_active_trail' => FALSE), $result1['link']);
    $result2 = array_shift($result);
    $this->assertEquals($items[1] + array('in_active_trail' => FALSE), $result2['link']);
  }

  /**
   * Tests buildTreeData with a more complex example.
   *
   * @covers ::buildTreeData
   * @covers ::doBuildTreeData
   */
  public function testBuildTreeWithComplexData() {
    $items = array(
      1 => array('mlid' => 1, 'depth' => 1, 'route_name' => 'example1', 'access' => TRUE, 'weight' => 0, 'title' => ''),
      2 => array('mlid' => 2, 'depth' => 1, 'route_name' => 'example2', 'access' => TRUE, 'weight' => 0, 'title' => ''),
      3 => array('mlid' => 3, 'depth' => 2, 'route_name' => 'example3', 'access' => TRUE, 'weight' => 0, 'title' => ''),
      4 => array('mlid' => 4, 'depth' => 3, 'route_name' => 'example4', 'access' => TRUE, 'weight' => 0, 'title' => ''),
      5 => array('mlid' => 5, 'depth' => 1, 'route_name' => 'example5', 'access' => TRUE, 'weight' => 0, 'title' => ''),
    );

    $tree = $this->menuTree->buildTreeData($items);

    // Validate that parent items #1, #2, and #5 exist on the root level.
    $this->assertEquals($items[1]['mlid'], $tree['50000  1']['link']['mlid']);
    $this->assertEquals($items[2]['mlid'], $tree['50000  2']['link']['mlid']);
    $this->assertEquals($items[5]['mlid'], $tree['50000  5']['link']['mlid']);

    // Validate that child item #4 exists at the correct location in the hierarchy.
    $this->assertEquals($items[4]['mlid'], $tree['50000  2']['below']['50000  3']['below']['50000  4']['link']['mlid']);
  }

  /**
   * Tests getActiveTrailIds().
   *
   * @covers ::getActiveTrailIds()
   */
  public function testGetActiveTrailIds() {
    $menu_link = array(
      'mlid' => 10,
      'route_name' => 'example1',
      'p1' => 3,
      'p2' => 2,
      'p3' => 1,
      'p4' => 4,
      'p5' => 9,
      'p6' => 5,
      'p7' => 6,
      'p8' => 7,
      'p9' => 8,
      'menu_name' => 'test_menu'
    );
    $this->menuTree->setPreferredMenuLink('test_menu', 'test/path', $menu_link);
    $request = (new Request());
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'test_route');
    $this->requestStack->push($request);
    $this->menuTree->setPath('test_menu', 'test/path');

    $trail = $this->menuTree->getActiveTrailIds('test_menu');
    $this->assertEquals(array(0 => 0, 3 => 3, 2 => 2, 1 => 1, 4 => 4, 9 => 9, 5 => 5, 6 => 6, 7 => 7), $trail);
  }

  /**
   * Tests getActiveTrailIds() without preferred link.
   *
   * @covers ::getActiveTrailIds()
   */
  public function testGetActiveTrailIdsWithoutPreferredLink() {
    $request = (new Request());
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'test_route');
    $this->requestStack->push($request);
    $this->menuTree->setPath('test_menu', 'test/path');

    $trail = $this->menuTree->getActiveTrailIds('test_menu');
    $this->assertEquals(array(0 => 0), $trail);
  }


  /**
   * Tests buildTree with simple menu_name and no parameters.
   */
  public function testBuildTreeWithoutParameters() {
    $language = new Language(array('id' => 'en'));
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue($language));

    // Setup query and the query result.
    $query = $this->getMock('Drupal\Core\Entity\Query\QueryInterface');
    $this->entityQueryFactory->expects($this->once())
      ->method('get')
      ->with('menu_link')
      ->will($this->returnValue($query));
    $query->expects($this->once())
      ->method('condition')
      ->with('menu_name', 'test_menu');
    $query->expects($this->once())
      ->method('execute')
      ->will($this->returnValue(array(1, 2, 3)));

    $storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $base = array(
      'access' => TRUE,
      'weight' => 0,
      'title' => 'title',
    );
    $menu_link = $base + array(
      'mlid' => 1,
      'p1' => 3,
      'p2' => 2,
      'p3' => 1,
    );
    $links[1] = $menu_link;
    $menu_link = $base + array(
      'mlid' => 3,
      'p1' => 3,
      'depth' => 1,
    );
    $links[3] = $menu_link;
    $menu_link = $base + array(
      'mlid' => 2,
      'p1' => 3,
      'p2' => 2,
      'depth' => 2,
    );
    $links[2] = $menu_link;
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->with(array(1, 2, 3))
      ->will($this->returnValue($links));
    $this->menuTree->setStorage($storage);

    // Ensure that static/non static caching works.
    // First setup no working caching.
    $this->cacheBackend->expects($this->at(0))
      ->method('get')
      ->with('links:test_menu:tree-data:en:35786c7117b4e38d0f169239752ce71158266ae2f6e4aa230fbbb87bd699c0e3')
      ->will($this->returnValue(FALSE));
    $this->cacheBackend->expects($this->at(1))
      ->method('set')
      ->with('links:test_menu:tree-data:en:35786c7117b4e38d0f169239752ce71158266ae2f6e4aa230fbbb87bd699c0e3', $this->anything(), Cache::PERMANENT, array('menu' => 'test_menu'));

    // Ensure that the static caching triggered.
    $this->cacheBackend->expects($this->exactly(1))
      ->method('get');

    $this->menuTree->buildTree('test_menu');
    $this->menuTree->buildTree('test_menu');
  }

  /**
   * Tests the output with a single level.
   *
   * @covers ::renderTree
   */
  public function testOutputWithSingleLevel() {
    $tree = array(
      '1' => array(
        'link' => array('mlid' => 1) + $this->defaultMenuLink,
        'below' => array(),
      ),
      '2' => array(
        'link' => array('mlid' => 2) + $this->defaultMenuLink,
        'below' => array(),
      ),
    );

    $output = $this->menuTree->renderTree($tree);

    // Validate that the - in main-menu is changed into an underscore
    $this->assertEquals($output['1']['#theme'], 'menu_link__main_menu', 'Hyphen is changed to an underscore on menu_link');
    $this->assertEquals($output['2']['#theme'], 'menu_link__main_menu', 'Hyphen is changed to an underscore on menu_link');
    $this->assertEquals($output['#theme_wrappers'][0], 'menu_tree__main_menu', 'Hyphen is changed to an underscore on menu_tree wrapper');
  }

  /**
   * Tests the output method with a complex example.
   *
   * @covers ::renderTree
   */
  public function testOutputWithComplexData() {
    $tree = array(
      '1'=> array(
        'link' => array('mlid' => 1, 'has_children' => 1, 'title' => 'Item 1', 'link_path' => 'a') + $this->defaultMenuLink,
        'below' => array(
          '2' => array('link' => array('mlid' => 2, 'title' => 'Item 2', 'link_path' => 'a/b') + $this->defaultMenuLink,
            'below' => array(
              '3' => array('link' => array('mlid' => 3, 'title' => 'Item 3', 'in_active_trail' => 0, 'link_path' => 'a/b/c') + $this->defaultMenuLink,
                'below' => array()),
              '4' => array('link' => array('mlid' => 4, 'title' => 'Item 4', 'in_active_trail' => 0, 'link_path' => 'a/b/d') + $this->defaultMenuLink,
                'below' => array())
            )
          )
        )
      ),
      '5' => array('link' => array('mlid' => 5, 'hidden' => 1, 'title' => 'Item 5', 'link_path' => 'e') + $this->defaultMenuLink, 'below' => array()),
      '6' => array('link' => array('mlid' => 6, 'title' => 'Item 6', 'in_active_trail' => 0, 'access' => 0, 'link_path' => 'f') + $this->defaultMenuLink, 'below' => array()),
      '7' => array('link' => array('mlid' => 7, 'title' => 'Item 7', 'in_active_trail' => 0, 'link_path' => 'g') + $this->defaultMenuLink, 'below' => array())
    );

    $output = $this->menuTree->renderTree($tree);

    // Looking for child items in the data
    $this->assertEquals( $output['1']['#below']['2']['#href'], 'a/b', 'Checking the href on a child item');
    $this->assertTrue(in_array('active-trail', $output['1']['#below']['2']['#attributes']['class']), 'Checking the active trail class');
    // Validate that the hidden and no access items are missing
    $this->assertFalse(isset($output['5']), 'Hidden item should be missing');
    $this->assertFalse(isset($output['6']), 'False access should be missing');
    // Item 7 is after a couple hidden items. Just to make sure that 5 and 6 are
    // skipped and 7 still included.
    $this->assertTrue(isset($output['7']), 'Item after hidden items is present');
  }

  /**
   * Tests menu tree access check with a single level.
   *
   * @covers ::checkAccess
   */
  public function testCheckAccessWithSingleLevel() {
    $items = array(
      array('mlid' => 1, 'route_name' => 'menu_test_1', 'depth' => 1, 'link_path' => 'menu_test/test_1', 'in_active_trail' => FALSE) + $this->defaultMenuLink,
      array('mlid' => 2, 'route_name' => 'menu_test_2', 'depth' => 1, 'link_path' => 'menu_test/test_2', 'in_active_trail' => FALSE) + $this->defaultMenuLink,
    );

    // Register a menuLinkTranslate to mock the access.
    $this->menuTree->menuLinkTranslateCallable = function(&$item) {
      $item['access'] = $item['mlid'] == 1;
    };

    // Build the menu tree and check access for all of the items.
    $tree = $this->menuTree->buildTreeData($items);

    $this->assertCount(1, $tree);
    $item = reset($tree);
    $this->assertEquals($items[0], $item['link']);
  }

}

class TestMenuTree extends MenuTree {

  /**
   * An alternative callable used for menuLinkTranslate.
   * @var callable
   */
  public $menuLinkTranslateCallable;

  /**
   * Stores the preferred menu link per menu and path.
   *
   * @var array
   */
  protected $preferredMenuLink;

  /**
   * {@inheritdoc}
   */
  protected function menuLinkTranslate(&$item) {
    if (isset($this->menuLinkTranslateCallable)) {
      call_user_func_array($this->menuLinkTranslateCallable, array(&$item));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function menuLinkGetPreferred($menu_name, $active_path) {
    return isset($this->preferredMenuLink[$menu_name][$active_path]) ? $this->preferredMenuLink[$menu_name][$active_path] : NULL;
  }

  /**
   * Set the storage.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The menu link storage.
   */
  public function setStorage(EntityStorageInterface $storage) {
    $this->menuLinkStorage = $storage;
  }

  /**
   * Sets the preferred menu link.
   *
   * @param string $menu_name
   *   The menu name.
   * @param string $active_path
   *   The active path.
   * @param array $menu_link
   *   The preferred menu link.
   */
  public function setPreferredMenuLink($menu_name, $active_path, $menu_link) {
    $this->preferredMenuLink[$menu_name][$active_path] = $menu_link;
  }

}

}

namespace {
  if (!defined('MENU_MAX_DEPTH')) {
    define('MENU_MAX_DEPTH', 9);
  }
}
