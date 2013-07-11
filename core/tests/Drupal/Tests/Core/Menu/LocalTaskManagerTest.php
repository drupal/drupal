<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Menu\LocalTaskManagerTest.
 */

namespace Drupal\Tests\Core\Menu;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\system\Plugin\Type\MenuLocalTaskManager;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zend\Stdlib\ArrayObject;

/**
 * Tests local tasks manager.
 *
 * @see \Drupal\system\Plugin\Type\MenuLocalTaskManager
 */
class LocalTaskManagerTest extends UnitTestCase {

  /**
   * The tested manager.
   *
   * @var \Drupal\system\Plugin\Type\MenuLocalTaskManager
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

    $this->setupLocalTaskManager();
  }

  /**
   * Tests the getLocalTasksForRoute method.
   *
   * @see \Drupal\system\Plugin\Type\MenuLocalTaskManager::getLocalTasksForRoute()
   */
  public function testGetLocalTasksForRouteSingleLevelTitle() {
    $definitions = array();
    $definitions['menu_local_task_test_tasks_settings'] = array(
      'id' => 'menu_local_task_test_tasks_settings',
      'route_name' => 'menu_local_task_test_tasks_settings',
      'title' => 'Settings',
      'tab_root_id' => 'menu_local_task_test_tasks_view',
      'class' => 'Drupal\menu_test\Plugin\Menu\MenuLocalTasksTestTasksSettings',
    );
    $definitions['menu_local_task_test_tasks_edit'] = array(
      'id' => 'menu_local_task_test_tasks_edit',
      'route_name' => 'menu_local_task_test_tasks_edit',
      'title' => 'Settings',
      'tab_root_id' => 'menu_local_task_test_tasks_view',
      'class' => 'Drupal\menu_test\Plugin\Menu\MenuLocalTasksTestTasksEdit',
      'weight' => 20,
    );
    $definitions['menu_local_task_test_tasks_view'] = array(
      'id' => 'menu_local_task_test_tasks_view',
      'route_name' => 'menu_local_task_test_tasks_view',
      'title' => 'Settings',
      'tab_root_id' => 'menu_local_task_test_tasks_view',
      'class' => 'Drupal\menu_test\Plugin\Menu\MenuLocalTasksTestTasksView',
    );

    $this->pluginDiscovery->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $mock_plugin = $this->getMock('Drupal\Core\Menu\LocalTaskInterface');

    $map = array(
      array('menu_local_task_test_tasks_settings', array(), $mock_plugin),
      array('menu_local_task_test_tasks_edit', array(), $mock_plugin),
      array('menu_local_task_test_tasks_view', array(), $mock_plugin),
    );
    $this->factory->expects($this->any())
      ->method('createInstance')
      ->will($this->returnValueMap($map));

    $this->setupLocalTaskManager();

    $local_tasks = $this->manager->getLocalTasksForRoute('menu_local_task_test_tasks_view');
    $this->assertEquals(array(0 => array(
      'menu_local_task_test_tasks_settings' => $mock_plugin,
      'menu_local_task_test_tasks_view' => $mock_plugin,
      'menu_local_task_test_tasks_edit' => $mock_plugin,
    )), $local_tasks);
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
   * Tests the getPath method.
   *
   * @see \Drupal\system\Plugin\Type\MenuLocalTaskManager::getPath()
   */
  public function testGetPath() {
    $menu_local_task = $this->getMock('Drupal\Core\Menu\LocalTaskInterface');
    $menu_local_task->expects($this->once())
      ->method('getPath');

    $this->controllerResolver->expects($this->once())
      ->method('getArguments')
      ->with($this->request, array($menu_local_task, 'getPath'))
      ->will($this->returnValue(array()));

    $this->manager->getPath($menu_local_task);
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

    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'discovery');
    $property->setAccessible(TRUE);
    $property->setValue($this->manager, $this->pluginDiscovery);

    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalTaskManager', 'factory');
    $property->setAccessible(TRUE);
    $property->setValue($this->manager, $this->factory);
  }

}

