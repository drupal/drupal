<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Menu\LocalActionManagerTest.
 */

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Menu\LocalActionInterface;
use Drupal\Core\Menu\LocalActionManager;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the local action manager.
 *
* @see \Drupal\Core\Menu\LocalActionManager
*/
class LocalActionManagerTest extends UnitTestCase {

  /**
   * The tested manager.
   *
   * @var \Drupal\Core\Menu\LocalActionManager
   */
  protected $manager;

  /**
   * The mocked controller resolver.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface|\PHPUnit_Framework_MockObject_MockObject
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
   * @var \Drupal\Core\Routing\RouteProviderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $routeProvider;

  /**
   * The mocked plugin discovery.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pluginDiscovery;

  /**
   * The plugin factory used in the test.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $factory;

  /**
   * The cache backend used in the test.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheBackend;

  /**
   * The plugin IDs for route 'test_route'.
   *
   * @var array
   */
  protected $pluginsTestRoute = array(
    'test_plugin_1',
    'test_plugin_2',
    'test_plugin_3',
  );

  /**
   * The plugin IDs for route 'test_route_2'.
   *
   * @var array
   */
  protected $pluginsTestRoute2 = array(
    'test_plugin_3',
    'test_plugin_4',
  );

  public static function getInfo() {
    return array(
      'name' => 'Local actions manager.',
      'description' => 'Tests local actions manager.',
      'group' => 'Menu',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->controllerResolver = $this->getMock('Symfony\Component\HttpKernel\Controller\ControllerResolverInterface');
    $this->request = new Request();
    $this->routeProvider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');
    $this->pluginDiscovery = $this->getMock('Drupal\Component\Plugin\Discovery\DiscoveryInterface');
    $this->factory = $this->getMock('Drupal\Component\Plugin\Factory\FactoryInterface');
    $this->cacheBackend = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
  }

  /**
   * Tests the getActionsForRoute method without an empty cache.
   */
  public function testGetActionsForRouteEmptyCache() {
    $this->cacheBackend->expects($this->at(0))
      ->method('get')
      ->with('local_action:en:test_route')
      ->will($this->returnValue(FALSE));

    $this->cacheBackend->expects($this->at(1))
      ->method('get')
      ->with('local_action:en')
      ->will($this->returnValue(FALSE));

    $definitions = $this->getTestPluginDefinitions();
    $this->cacheBackend->expects($this->at(2))
      ->method('set')
      ->with('local_action:en', $definitions);

    $this->cacheBackend->expects($this->at(3))
      ->method('set')
      ->with('local_action:en:test_route', $this->pluginsTestRoute);

    $mock_plugin = $this->getMock('Drupal\Core\Menu\LocalActionInterface');
    $this->setupPluginFactory($mock_plugin);
    $this->setupPluginDiscovery();
    $this->setupLocalActionManager();

    $expected_actions = array();
    foreach ($this->pluginsTestRoute as $plugin_id) {
      $expected_actions[$plugin_id] = $mock_plugin;
    }

    $this->assertEquals($expected_actions, $this->manager->getActionsForRoute('test_route'));
  }

  /**
   * Tests the getActionsForRoute method with a filled cache.
   */
  public function testGetActionsForRouteFilledCache() {
    $definitions = $this->getTestPluginDefinitions();

    // Ensures that a call with a setup cache does not result in a full
    // rescan of all the plugins.
    $this->cacheBackend->expects($this->at(0))
      ->method('get')
      ->with('local_action:en:test_route')
      ->will($this->returnValue((object) array('data' => $this->pluginsTestRoute)));

    $this->pluginDiscovery->expects($this->never())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $this->cacheBackend->expects($this->never())
      ->method('set');

    $mock_plugin = $this->getMock('Drupal\Core\Menu\LocalActionInterface');
    $this->setupPluginFactory($mock_plugin);
    $this->setupLocalActionManager();

    $expected_actions = array();
    foreach ($this->pluginsTestRoute as $plugin_id) {
      $expected_actions[$plugin_id] = $mock_plugin;
    }

    $this->assertEquals($expected_actions, $this->manager->getActionsForRoute('test_route'));
  }

  /**
   * Tests the getActionsForRoute method with a filled cache for both routes.
   */
  public function testGetActionsForFullFilledRouteCache() {
    $definitions = $this->getTestPluginDefinitions();

    // Ensures that a call with a setup cache does not result in a full
    // rescan of all the plugins.
    $this->cacheBackend->expects($this->at(0))
      ->method('get')
      ->with('local_action:en:test_route')
      ->will($this->returnValue((object) array('data' => $this->pluginsTestRoute)));
    $this->cacheBackend->expects($this->at(1))
      ->method('get')
      ->with('local_action:en:test_route_2')
      ->will($this->returnValue((object) array('data' => $this->pluginsTestRoute2)));

    $this->cacheBackend->expects($this->exactly(2))
      ->method('get');

    $this->pluginDiscovery->expects($this->never())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $this->cacheBackend->expects($this->never())
      ->method('set');

    $mock_plugin = $this->getMock('Drupal\Core\Menu\LocalActionInterface');
    $this->setupPluginFactory($mock_plugin);
    $this->setupLocalActionManager();

    $expected_actions = array();
    foreach ($this->pluginsTestRoute as $plugin_id) {
      $expected_actions[$plugin_id] = $mock_plugin;
    }
    $expected_actions_2 = array();
    foreach ($this->pluginsTestRoute2 as $plugin_id) {
      $expected_actions_2[$plugin_id] = $mock_plugin;
    }

    $this->assertEquals($expected_actions, $this->manager->getActionsForRoute('test_route'));
    $this->assertEquals($expected_actions_2, $this->manager->getActionsForRoute('test_route_2'));
    $this->assertEquals($expected_actions, $this->manager->getActionsForRoute('test_route'));
    $this->assertEquals($expected_actions_2, $this->manager->getActionsForRoute('test_route_2'));
  }

  /**
   * Setups the local actions manager.
   */
  protected function setupLocalActionManager() {
    $this->manager = $this
      ->getMockBuilder('Drupal\Core\Menu\LocalActionManager')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalActionManager', 'controllerResolver');
    $property->setAccessible(TRUE);
    $property->setValue($this->manager, $this->controllerResolver);

    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalActionManager', 'request');
    $property->setAccessible(TRUE);
    $property->setValue($this->manager, $this->request);

    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalActionManager', 'discovery');
    $property->setAccessible(TRUE);
    $property->setValue($this->manager, $this->pluginDiscovery);

    $property = new \ReflectionProperty('Drupal\Core\Menu\LocalActionManager', 'factory');
    $property->setAccessible(TRUE);
    $property->setValue($this->manager, $this->factory);

    $language_manager = $this->getMockBuilder('Drupal\Core\Language\LanguageManager')
      ->disableOriginalConstructor()
      ->getMock();
    $language_manager->expects($this->any())
      ->method('getLanguage')
      ->will($this->returnValue(new Language(array('id' => 'en'))));

    $this->manager->setCacheBackend($this->cacheBackend, $language_manager, 'local_action');
  }

  /**
   * Setup the plugin discovery.
   */
  protected function setupPluginDiscovery() {
    $definitions = $this->getTestPluginDefinitions();

    $this->pluginDiscovery->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));
  }

  /**
   * Setups the plugin factory.
   *
   * @param \Drupal\Core\Menu\LocalActionInterface $mock_plugin
   *   A mocked plugin instance.
   */
  protected function setupPluginFactory(LocalActionInterface $mock_plugin) {
    $map = array(
      array('test_plugin_1', array(), $mock_plugin),
      array('test_plugin_2', array(), $mock_plugin),
      array('test_plugin_3', array(), $mock_plugin),
      array('test_plugin_4', array(), $mock_plugin),
    );
    $this->factory->expects($this->any())
      ->method('createInstance')
      ->will($this->returnValueMap($map));
  }

  /**
   * Returns plugin definitions for the test.
   *
   * @return array
   *   An array of local action plugin definition.
   */
  protected function getTestPluginDefinitions() {
    $definitions = array();
    $definitions['test_plugin_1'] = array(
      'id' => 'test_plugin_1',
      'title' => 'Test plugin 1',
      'route_name' => 'test_route_1',
      'appears_on' => array('test_route'),
    );
    $definitions['test_plugin_2'] = array(
      'id' => 'test_plugin_2',
      'title' => 'Test plugin 2',
      'route_name' => 'test_route_2',
      'appears_on' => array('test_route'),
    );
    $definitions['test_plugin_3'] = array(
      'id' => 'test_plugin_3',
      'title' => 'Test plugin 3',
      'route_name' => 'test_route_3',
      'appears_on' => array('test_route', 'test_route_2'),
    );
    $definitions['test_plugin_4'] = array(
      'id' => 'test_plugin_4',
      'title' => 'Test plugin 4',
      'route_name' => 'test_route_4',
      'appears_on' => array('test_route_2'),
    );
    return $definitions;
  }

}
