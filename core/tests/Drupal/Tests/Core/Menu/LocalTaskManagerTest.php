<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Menu\LocalTaskManagerTest.
 */

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zend\Stdlib\ArrayObject;

/**
 * Tests local tasks manager.
 *
 * @see \Drupal\Core\Menu\LocalTaskManager
 */
class LocalTaskManagerTest extends UnitTestCase {

  /**
   * The tested manager.
   *
   * @var \Drupal\Core\Menu\LocalTaskManager
   */
  protected $manager;

  /**
   * The mocked controller resolver.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $controllerResolver;

  /**
   * The test request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The mocked route provider.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $routeProvider;

  /**
   * The mocked plugin discovery.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $pluginDiscovery;

  /**
   * The plugin factory used in the test.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $factory;

  /**
   * The cache backend used in the test.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheBackend;

  /**
   * The mocked access manager.
   *
   * @var \Drupal\Core\Access\AccessManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $accessManager;

  public static function getInfo() {
    return array(
      'name' => 'Local tasks manager.',
      'description' => 'Tests local tasks manager.',
      'group' => 'Menu',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->controllerResolver = $this->getMock('Symfony\Component\HttpKernel\Controller\ControllerResolverInterface');
    $this->request = new Request();
    $this->routeProvider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');
    $this->pluginDiscovery = $this->getMock('Drupal\Component\Plugin\Discovery\DiscoveryInterface');
    $this->factory = $this->getMock('Drupal\Component\Plugin\Factory\FactoryInterface');
    $this->cacheBackend = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->accessManager = $this->getMockBuilder('Drupal\Core\Access\AccessManager')
      ->disableOriginalConstructor()
      ->getMock();

    $this->setupLocalTaskManager();
  }

  /**
   * Tests the getLocalTasksForRoute method.
   *
   * @see \Drupal\system\Plugin\Type\MenuLocalTaskManager::getLocalTasksForRoute()
   */
  public function testGetLocalTasksForRouteSingleLevelTitle() {
    $definitions = $this->getLocalTaskFixtures();

    $this->pluginDiscovery->expects($this->once())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $mock_plugin = $this->getMock('Drupal\Core\Menu\LocalTaskInterface');

    $this->setupFactory($mock_plugin);
    $this->setupLocalTaskManager();

    $local_tasks = $this->manager->getLocalTasksForRoute('menu_local_task_test_tasks_view');

    $result = array(
      0 => array(
        'menu_local_task_test_tasks_settings' => $mock_plugin,
        'menu_local_task_test_tasks_view' => $mock_plugin,
        'menu_local_task_test_tasks_edit' => $mock_plugin,
      )
    );

    $this->assertEquals($result, $local_tasks);
  }

  /**
   * Tests the cache of the local task manager with an empty initial cache.
   */
  public function testGetLocalTaskForRouteWithEmptyCache() {
    $definitions = $this->getLocalTaskFixtures();

    $this->pluginDiscovery->expects($this->once())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $mock_plugin = $this->getMock('Drupal\Core\Menu\LocalTaskInterface');
    $this->setupFactory($mock_plugin);

    $this->setupLocalTaskManager();

    $result = $this->getLocalTasksForRouteResult($mock_plugin);

    $this->cacheBackend->expects($this->at(0))
      ->method('get')
      ->with('local_task:en:menu_local_task_test_tasks_view');

    $this->cacheBackend->expects($this->at(1))
      ->method('get')
      ->with('local_task:en');

    $this->cacheBackend->expects($this->at(2))
      ->method('set')
      ->with('local_task:en', $definitions, CacheBackendInterface::CACHE_PERMANENT);

    $expected_set = $this->getLocalTasksCache();

    $this->cacheBackend->expects($this->at(3))
      ->method('set')
      ->with('local_task:en:menu_local_task_test_tasks_view', $expected_set, CacheBackendInterface::CACHE_PERMANENT, array('local_task'));

    $local_tasks = $this->manager->getLocalTasksForRoute('menu_local_task_test_tasks_view');
    $this->assertEquals($result, $local_tasks);
  }

  /**
   * Tests the cache of the local task manager with a filled initial cache.
   */
  public function testGetLocalTaskForRouteWithFilledCache() {
    $this->pluginDiscovery->expects($this->never())
      ->method('getDefinitions');

    $mock_plugin = $this->getMock('Drupal\Core\Menu\LocalTaskInterface');
    $this->setupFactory($mock_plugin);

    $this->setupLocalTaskManager();

    $result = $this->getLocalTasksCache($mock_plugin);

    $this->cacheBackend->expects($this->at(0))
      ->method('get')
      ->with('local_task:en:menu_local_task_test_tasks_view')
      ->will($this->returnValue((object) array('data' => $result)));

    $this->cacheBackend->expects($this->never())
      ->method('set');

    $result = $this->getLocalTasksForRouteResult($mock_plugin);
    $local_tasks = $this->manager->getLocalTasksForRoute('menu_local_task_test_tasks_view');
    $this->assertEquals($result, $local_tasks);
  }

  /**
   * Tests the getTitle method.
   *
   * @see \Drupal\system\Plugin\Type\MenuLocalTaskManager::getTitle()
   */
  public function testGetTitle() {
    $menu_local_task = $this->getMock('Drupal\Core\Menu\LocalTaskInterface');
    $menu_local_task->expects($this->once())
      ->method('getTitle');

    $this->controllerResolver->expects($this->once())
      ->method('getArguments')
      ->with($this->request, array($menu_local_task, 'getTitle'))
      ->will($this->returnValue(array()));

    $this->manager->getTitle($menu_local_task);
  }

  /**
   * Setups the local task manager for the test.
   */
  protected function setupLocalTaskManager() {
    $this->manager = $this
      ->getMockBuilder('Drupal\Core\Menu\LocalTaskManager')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'controllerResolver');
    $property->setAccessible(TRUE);
    $property->setValue($this->manager, $this->controllerResolver);

    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'request');
    $property->setAccessible(TRUE);
    $property->setValue($this->manager, $this->request);

    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'accessManager');
    $property->setAccessible(TRUE);
    $property->setValue($this->manager, $this->accessManager);

    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'discovery');
    $property->setAccessible(TRUE);
    $property->setValue($this->manager, $this->pluginDiscovery);

    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'factory');
    $property->setAccessible(TRUE);
    $property->setValue($this->manager, $this->factory);

    $language_manager = $this->getMockBuilder('Drupal\Core\Language\LanguageManager')
      ->disableOriginalConstructor()
      ->getMock();
    $language_manager->expects($this->any())
      ->method('getLanguage')
      ->will($this->returnValue(new Language(array('id' => 'en'))));

    $this->manager->setCacheBackend($this->cacheBackend, $language_manager, 'local_task');
  }

  /**
   * Return some local tasks plugin definitions.
   *
   * @return array
   *   An array of plugin definition keyed by plugin ID.
   */
  protected function getLocalTaskFixtures() {
    $definitions = array();
    $definitions['menu_local_task_test_tasks_settings'] = array(
      'id' => 'menu_local_task_test_tasks_settings',
      'route_name' => 'menu_local_task_test_tasks_settings',
      'title' => 'Settings',
      'tab_root_id' => 'menu_local_task_test_tasks_view',
    );
    $definitions['menu_local_task_test_tasks_edit'] = array(
      'id' => 'menu_local_task_test_tasks_edit',
      'route_name' => 'menu_local_task_test_tasks_edit',
      'title' => 'Settings',
      'tab_root_id' => 'menu_local_task_test_tasks_view',
      'weight' => 20,
    );
    $definitions['menu_local_task_test_tasks_view'] = array(
      'id' => 'menu_local_task_test_tasks_view',
      'route_name' => 'menu_local_task_test_tasks_view',
      'title' => 'Settings',
      'tab_root_id' => 'menu_local_task_test_tasks_view',
    );
    // Add the defaults from the LocalTaskManager.
    foreach ($definitions as $id => &$info) {
      $info += array(
        'id' => '',
        'route_name' => '',
        'route_parameters' => array(),
        'title' => '',
        'tab_root_id' => '',
        'tab_parent_id' => NULL,
        'weight' => 0,
        'options' => array(),
        'class' => 'Drupal\Core\Menu\LocalTaskDefault',
      );
    }
    return $definitions;
  }

  /**
   * Setups the plugin factory with some local task plugins.
   *
   * @param \PHPUnit_Framework_MockObject_MockObject $mock_plugin
   *   The mock plugin.
   */
  protected function setupFactory($mock_plugin) {
    $map = array(
      array('menu_local_task_test_tasks_settings', array(), $mock_plugin),
      array('menu_local_task_test_tasks_edit', array(), $mock_plugin),
      array('menu_local_task_test_tasks_view', array(), $mock_plugin),
    );
    $this->factory->expects($this->any())
      ->method('createInstance')
      ->will($this->returnValueMap($map));
  }

  /**
   * Returns an expected result for getLocalTasksForRoute.
   *
   * @param \PHPUnit_Framework_MockObject_MockObject $mock_plugin
   *   The mock plugin.
   *
   * @return array
   *   The expected result, keyed by local task leve.
   */
  protected function getLocalTasksForRouteResult($mock_plugin) {
    $result = array(
      0 => array(
        'menu_local_task_test_tasks_settings' => $mock_plugin,
        'menu_local_task_test_tasks_view' => $mock_plugin,
        'menu_local_task_test_tasks_edit' => $mock_plugin,
      )
    );
    return $result;
  }

  /**
   * Returns the cache entry expected when running getLocalTaskForRoute().
   *
   * @return array
   */
  protected function getLocalTasksCache() {
    return array(
      'tab_root_ids' => array(
        'menu_local_task_test_tasks_view' => 'menu_local_task_test_tasks_view',
      ),
      'parents' => array(
        'menu_local_task_test_tasks_view' => 1,
      ),
      'children' => array(
        '> menu_local_task_test_tasks_view' => $this->getLocalTaskFixtures(),
      )
    );
  }

}

