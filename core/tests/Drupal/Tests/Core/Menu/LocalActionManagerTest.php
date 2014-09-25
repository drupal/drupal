<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Menu\LocalActionManagerTest.
 */

namespace Drupal\Tests\Core\Menu;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Menu\LocalActionManager;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

/**
 * @coversDefaultClass \Drupal\Core\Menu\LocalActionManager
 * @group Menu
 */
class LocalActionManagerTest extends UnitTestCase {

  /**
   * The mocked controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $controllerResolver;

  /**
   * The mocked request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $request;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The mocked router provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $routeProvider;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheBackend;

  /**
   * The mocked access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $accessManager;

  /**
   * The mocked account.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $account;

  /**
   * The mocked factory.
   *
   * @var \Drupal\Component\Plugin\Factory\FactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $factory;

  /**
   * The mocked plugin discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $discovery;

  /**
   * The tested local action manager
   *
   * @var \Drupal\Tests\Core\Menu\TestLocalActionManager
   */
  protected $localActionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->controllerResolver = $this->getMock('Drupal\Core\Controller\ControllerResolverInterface');
    $this->request = $this->getMock('Symfony\Component\HttpFoundation\Request');
    $this->routeProvider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->cacheBackend = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');

    $this->accessManager = $this->getMock('Drupal\Core\Access\AccessManagerInterface');
    $this->accessManager->expects($this->any())
      ->method('checkNamedRoute')
      ->will($this->returnValue(FALSE));
    $this->account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->discovery = $this->getMock('Drupal\Component\Plugin\Discovery\DiscoveryInterface');
    $this->factory = $this->getMock('Drupal\Component\Plugin\Factory\FactoryInterface');

    $this->localActionManager = new TestLocalActionManager($this->controllerResolver, $this->request, $this->routeProvider, $this->moduleHandler, $this->cacheBackend, $this->accessManager, $this->account, $this->discovery, $this->factory);
  }

  /**
   * @covers \Drupal\Core\Menu\LocalActionManager::getTitle()
   */
  public function testGetTitle() {
    $local_action = $this->getMock('Drupal\Core\Menu\LocalActionInterface');
    $local_action->expects($this->once())
      ->method('getTitle')
      ->with('test');

    $this->controllerResolver->expects($this->once())
      ->method('getArguments')
      ->with($this->request, array($local_action, 'getTitle'))
      ->will($this->returnValue(array('test')));

    $this->localActionManager->getTitle($local_action);
  }

  /**
   * @covers \Drupal\Core\Menu\LocalActionManager::getActionsForRoute()
   *
   * @dataProvider getActionsForRouteProvider
   */
  public function testGetActionsForRoute($route_appears, array $plugin_definitions, array $expected_actions) {
    $this->discovery->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($plugin_definitions));
    $map = array();
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      $plugin = $this->getMock('Drupal\Core\Menu\LocalActionInterface');
      $plugin->expects($this->any())
        ->method('getRouteName')
        ->will($this->returnValue($plugin_definition['route_name']));
      $plugin->expects($this->any())
        ->method('getRouteParameters')
        ->will($this->returnValue(isset($plugin_definition['route_parameters']) ? $plugin_definition['route_parameters'] : array()));
      $plugin->expects($this->any())
        ->method('getTitle')
        ->will($this->returnValue($plugin_definition['title']));
      $this->controllerResolver->expects($this->any())
      ->method('getArguments')
      ->with($this->request, array($plugin, 'getTitle'))
      ->will($this->returnValue(array()));

      $plugin->expects($this->any())
        ->method('getWeight')
        ->will($this->returnValue($plugin_definition['weight']));
      $this->controllerResolver->expects($this->any())
        ->method('getArguments')
        ->with($this->request, array($plugin, 'getTitle'))
        ->will($this->returnValue(array()));
      $map[] = array($plugin_id, array(), $plugin);
    }
    $this->factory->expects($this->any())
      ->method('createInstance')
      ->will($this->returnValueMap($map));

    $this->assertEquals($expected_actions, $this->localActionManager->getActionsForRoute($route_appears));
  }

  public function getActionsForRouteProvider() {
    // Single available and single expected plugins.
    $data[] = array(
      'test_route',
      array(
        'plugin_id_1' => array(
          'appears_on' => array(
            'test_route',
          ),
          'route_name' => 'test_route_2',
          'title' => 'Plugin ID 1',
          'weight' => 0,
        ),
      ),
      array(
        'plugin_id_1' => array(
          '#theme' => 'menu_local_action',
          '#link' => array(
            'title' => 'Plugin ID 1',
            'route_name' => 'test_route_2',
            'route_parameters' => array(),
            'localized_options' => '',
          ),
          '#access' => FALSE,
          '#weight' => 0,
        ),
      ),
    );
    // Multiple available and single expected plugins.
    $data[] = array(
      'test_route',
      array(
        'plugin_id_1' => array(
          'appears_on' => array(
            'test_route',
          ),
          'route_name' => 'test_route_2',
          'title' => 'Plugin ID 1',
          'weight' => 0,
        ),
        'plugin_id_2' => array(
          'appears_on' => array(
            'test_route2',
          ),
          'route_name' => 'test_route_3',
          'title' => 'Plugin ID 2',
          'weight' => 0,
        ),
      ),
      array(
        'plugin_id_1' => array(
          '#theme' => 'menu_local_action',
          '#link' => array(
            'title' => 'Plugin ID 1',
            'route_name' => 'test_route_2',
            'route_parameters' => array(),
            'localized_options' => '',
          ),
          '#access' => FALSE,
          '#weight' => 0,
        ),
      ),
    );

    // Multiple available and multiple expected plugins and specified weight.
    $data[] = array(
      'test_route',
      array(
        'plugin_id_1' => array(
          'appears_on' => array(
            'test_route',
          ),
          'route_name' => 'test_route_2',
          'title' => 'Plugin ID 1',
          'weight' => 1,
        ),
        'plugin_id_2' => array(
          'appears_on' => array(
            'test_route',
          ),
          'route_name' => 'test_route_3',
          'title' => 'Plugin ID 2',
          'weight' => 0,
        ),
      ),
      array(
        'plugin_id_1' => array(
          '#theme' => 'menu_local_action',
          '#link' => array(
            'title' => 'Plugin ID 1',
            'route_name' => 'test_route_2',
            'route_parameters' => array(),
            'localized_options' => '',
          ),
          '#access' => FALSE,
          '#weight' => 1,
        ),
        'plugin_id_2' => array(
          '#theme' => 'menu_local_action',
          '#link' => array(
            'title' => 'Plugin ID 2',
            'route_name' => 'test_route_3',
            'route_parameters' => array(),
            'localized_options' => '',
          ),
          '#access' => FALSE,
          '#weight' => 0,
        ),
      ),
    );

    // Two plugins with the same route name but different route parameters.
    $data[] = array(
      'test_route',
      array(
        'plugin_id_1' => array(
          'appears_on' => array(
            'test_route',
          ),
          'route_name' => 'test_route_2',
          'route_parameters' => array('test1'),
          'title' => 'Plugin ID 1',
          'weight' => 1,
        ),
        'plugin_id_2' => array(
          'appears_on' => array(
            'test_route',
          ),
          'route_name' => 'test_route_2',
          'route_parameters' => array('test2'),
          'title' => 'Plugin ID 2',
          'weight' => 0,
        ),
      ),
      array(
        'plugin_id_1' => array(
          '#theme' => 'menu_local_action',
          '#link' => array(
            'title' => 'Plugin ID 1',
            'route_name' => 'test_route_2',
            'route_parameters' => array('test1'),
            'localized_options' => '',
          ),
          '#access' => FALSE,
          '#weight' => 1,
        ),
        'plugin_id_2' => array(
          '#theme' => 'menu_local_action',
          '#link' => array(
            'title' => 'Plugin ID 2',
            'route_name' => 'test_route_2',
            'route_parameters' => array('test2'),
            'localized_options' => '',
          ),
          '#access' => FALSE,
          '#weight' => 0,
        ),
      ),
    );

    return $data;
  }

}

class TestLocalActionManager extends LocalActionManager {

  public function __construct(ControllerResolverInterface $controller_resolver, Request $request, RouteProviderInterface $route_provider, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend, AccessManagerInterface $access_manager, AccountInterface $account, DiscoveryInterface $discovery, FactoryInterface $factory) {
    $this->discovery = $discovery;
    $this->factory = $factory;
    $this->routeProvider = $route_provider;
    $this->accessManager = $access_manager;
    $this->account = $account;
    $this->controllerResolver = $controller_resolver;
    $this->requestStack = new RequestStack();
    $this->requestStack->push($request);
    $this->moduleHandler = $module_handler;
    $this->alterInfo('menu_local_actions');
    $this->setCacheBackend($cache_backend, 'local_action_plugins', array('local_action'));
  }

}
