<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Access\AccessManagerTest.
 */

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessInterface;
use Drupal\Core\Access\AccessManager;
use Drupal\Core\Access\DefaultAccessCheck;
use Drupal\system\Tests\Routing\MockRouteProvider;
use Drupal\Tests\UnitTestCase;
use Drupal\router_test\Access\DefinedTestAccessCheck;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the access manager.
 *
 * @see \Drupal\Core\Access\AccessManager
 */
class AccessManagerTest extends UnitTestCase {

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * The collection of routes, which are tested.
   *
   * @var \Symfony\Component\Routing\RouteCollection
   */
  protected $routeCollection;

  /**
   * The access manager to test.
   *
   * @var \Drupal\Core\Access\AccessManager
   */
  protected $accessManager;

  /**
   * The route provider.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $routeProvider;

  /**
   * The url generator
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $urlGenerator;

  /**
   * The parameter converter.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $paramConverter;

  public static function getInfo() {
    return array(
      'name' => 'Access manager tests',
      'description' => 'Test for the AccessManager object.',
      'group' => 'Routing',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container = new ContainerBuilder();

    $this->routeCollection = new RouteCollection();
    $this->routeCollection->add('test_route_1', new Route('/test-route-1'));
    $this->routeCollection->add('test_route_2', new Route('/test-route-2', array(), array('_access' => 'TRUE')));
    $this->routeCollection->add('test_route_3', new Route('/test-route-3', array(), array('_access' => 'FALSE')));
    $this->routeCollection->add('test_route_4', new Route('/test-route-4/{value}', array(), array('_access' => 'TRUE')));

    $this->routeProvider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');
    $map = array();
    foreach ($this->routeCollection->all() as $name => $route) {
      $map[] = array($name, array(), $route);
    }
    $map[] = array('test_route_4', array('value' => 'example'), $this->routeCollection->get('test_route_4'));
    $this->routeProvider->expects($this->any())
      ->method('getRouteByName')
      ->will($this->returnValueMap($map));

    $map = array();
    $map[] = array('test_route_1', array(), '/test-route-1');
    $map[] = array('test_route_2', array(), '/test-route-2');
    $map[] = array('test_route_3', array(), '/test-route-3');
    $map[] = array('test_route_4', array('value' => 'example'), '/test-route-4/example');

    $this->urlGenerator = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
    $this->urlGenerator->expects($this->any())
      ->method('generate')
      ->will($this->returnValueMap($map));

    $this->paramConverter = $this->getMock('\Drupal\Core\ParamConverter\ParamConverterManager');

    $this->accessManager = new AccessManager($this->routeProvider, $this->urlGenerator, $this->paramConverter);
    $this->accessManager->setContainer($this->container);
  }

  /**
   * Tests \Drupal\Core\Access\AccessManager::setChecks().
   */
  public function testSetChecks() {
    // Check setChecks without any access checker defined yet.
    $this->accessManager->setChecks($this->routeCollection);

    foreach ($this->routeCollection->all() as $route) {
      $this->assertNull($route->getOption('_access_checks'));
    }

    $this->setupAccessChecker();

    $this->accessManager->setChecks($this->routeCollection);

    $this->assertEquals($this->routeCollection->get('test_route_1')->getOption('_access_checks'), NULL);
    $this->assertEquals($this->routeCollection->get('test_route_2')->getOption('_access_checks'), array('test_access_default'));
    $this->assertEquals($this->routeCollection->get('test_route_3')->getOption('_access_checks'), array('test_access_default'));
  }

  /**
   * Tests \Drupal\Core\Access\AccessManager::check().
   */
  public function testCheck() {
    $request = new Request();

    // Check check without any access checker defined yet.
    foreach ($this->routeCollection->all() as $route) {
      $this->assertFalse($this->accessManager->check($route, $request));
    }

    $this->setupAccessChecker();

    // An access checker got setup, but the routes haven't been setup using
    // setChecks.
    foreach ($this->routeCollection->all() as $route) {
      $this->assertFalse($this->accessManager->check($route, $request));
    }

    $this->accessManager->setChecks($this->routeCollection);

    $this->assertFalse($this->accessManager->check($this->routeCollection->get('test_route_1'), $request));
    $this->assertTrue($this->accessManager->check($this->routeCollection->get('test_route_2'), $request));
    $this->assertFalse($this->accessManager->check($this->routeCollection->get('test_route_3'), $request));
  }

  /**
   * Provides data for the conjunction test.
   *
   * @return array
   *   An array of data for check conjunctions.
   *
   * @see \Drupal\Tests\Core\Access\AccessManagerTest::testCheckConjunctions()
   */
  public function providerTestCheckConjunctions() {
    $access_configurations = array();
    $access_configurations[] = array(
      'conjunction' => 'ALL',
      'name' => 'test_route_4',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::KILL,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ALL',
      'name' => 'test_route_5',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::DENY,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ALL',
      'name' => 'test_route_6',
      'condition_one' => AccessCheckInterface::KILL,
      'condition_two' => AccessCheckInterface::DENY,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ALL',
      'name' => 'test_route_7',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::ALLOW,
      'expected' => TRUE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ALL',
      'name' => 'test_route_8',
      'condition_one' => AccessCheckInterface::KILL,
      'condition_two' => AccessCheckInterface::KILL,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ALL',
      'name' => 'test_route_9',
      'condition_one' => AccessCheckInterface::DENY,
      'condition_two' => AccessCheckInterface::DENY,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ANY',
      'name' => 'test_route_10',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::KILL,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ANY',
      'name' => 'test_route_11',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::DENY,
      'expected' => TRUE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ANY',
      'name' => 'test_route_12',
      'condition_one' => AccessCheckInterface::KILL,
      'condition_two' => AccessCheckInterface::DENY,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ANY',
      'name' => 'test_route_13',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::ALLOW,
      'expected' => TRUE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ANY',
      'name' => 'test_route_14',
      'condition_one' => AccessCheckInterface::KILL,
      'condition_two' => AccessCheckInterface::KILL,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ANY',
      'name' => 'test_route_15',
      'condition_one' => AccessCheckInterface::DENY,
      'condition_two' => AccessCheckInterface::DENY,
      'expected' => FALSE,
    );

    return $access_configurations;
  }

  /**
   * Test \Drupal\Core\Access\AccessManager::check() with conjunctions.
   *
   * @dataProvider providerTestCheckConjunctions
   */
  public function testCheckConjunctions($conjunction, $name, $condition_one, $condition_two, $expected_access) {
    $this->setupAccessChecker();
    $access_check = new DefinedTestAccessCheck();
    $this->container->register('test_access_defined', $access_check);
    $this->accessManager->addCheckService('test_access_defined');

    $request = new Request();

    $route_collection = new RouteCollection();
    // Setup a test route for each access configuration.
    $requirements = array(
      '_access' => static::convertAccessCheckInterfaceToString($condition_one),
      '_test_access' => static::convertAccessCheckInterfaceToString($condition_two),
    );
    $options = array('_access_mode' => $conjunction);
    $route = new Route($name, array(), $requirements, $options);
    $route_collection->add($name, $route);

    $this->accessManager->setChecks($route_collection);
    $this->assertSame($this->accessManager->check($route, $request), $expected_access);
  }

  /**
   * Tests the static access checker interface.
   */
  public function testStaticAccessCheckInterface() {
    $mock_static = $this->getMock('Drupal\Core\Access\StaticAccessCheckInterface');
    $mock_static->expects($this->once())
      ->method('appliesTo')
      ->will($this->returnValue(array('_access')));

    $this->container->set('test_static_access', $mock_static);
    $this->accessManager->addCheckService('test_static_access');

    $this->accessManager->setChecks($this->routeCollection);
  }

  /**
   * Tests the checkNamedRoute method.
   *
   * @see \Drupal\Core\Access\AccessManager::checkNamedRoute()
   */
  public function testCheckNamedRoute() {
    $this->setupAccessChecker();
    $this->accessManager->setChecks($this->routeCollection);

    // Tests the access with routes without parameters.
    $request = new Request();
    $this->assertTrue($this->accessManager->checkNamedRoute('test_route_2', array(), $request));
    $this->assertFalse($this->accessManager->checkNamedRoute('test_route_3', array(), $request));

    // Tests the access with routes with parameters with given request.
    $request = new Request();
    $request->attributes->set('value', 'example');
    $request->attributes->set('value2', 'example2');
    $this->assertTrue($this->accessManager->checkNamedRoute('test_route_4', array(), $request));

    // Tests the access with routes without given request.
    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->accessManager->setRequest(new Request(array(), array(), array('_account' => $account)));

    $this->paramConverter->expects($this->at(0))
      ->method('enhance')
      ->will($this->returnValue(array()));

    $this->paramConverter->expects($this->at(1))
      ->method('enhance')
      ->will($this->returnValue(array()));

    // Tests the access with routes with parameters without given request.
    $this->assertTrue($this->accessManager->checkNamedRoute('test_route_2', array()));
    $this->assertTrue($this->accessManager->checkNamedRoute('test_route_4', array('value' => 'example')));
  }

  /**
   * Tests the checkNamedRoute with upcasted values.
   *
   * @see \Drupal\Core\Access\AccessManager::checkNamedRoute()
   */
  public function testCheckNamedRouteWithUpcastedValues() {
    $account = $this->getMock('Drupal\Core\Session\AccountInterface');

    $this->routeCollection = new RouteCollection();
    $route = new Route('/test-route-1/{value}', array(), array('_test_access' => 'TRUE'));
    $this->routeCollection->add('test_route_1', $route);

    $this->routeProvider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');
    $this->routeProvider->expects($this->any())
      ->method('getRouteByName')
      ->with('test_route_1', array('value' => 'example'))
      ->will($this->returnValue($route));

    $map = array();
    $map[] = array('test_route_1', array('value' => 'example'), '/test-route-1/example');

    $this->urlGenerator = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
    $this->urlGenerator->expects($this->any())
      ->method('generate')
      ->will($this->returnValueMap($map));

    $this->paramConverter = $this->getMock('\Drupal\Core\ParamConverter\ParamConverterManager');
    $this->paramConverter->expects($this->at(0))
      ->method('enhance')
      ->will($this->returnValue(array('value' => 'upcasted_value')));


    $subrequest = Request::create('/test-route-1/example');
    $class = $this->getMockClass('Symfony\Component\HttpFoundation\Request', array('create'));
    $class::staticExpects($this->any())
      ->method('create')
      ->with('/test-route-1/example')
      ->will($this->returnValue($subrequest));

    $this->accessManager = new AccessManager($this->routeProvider, $this->urlGenerator, $this->paramConverter);
    $this->accessManager->setContainer($this->container);
    $this->accessManager->setRequest(new Request(array(), array(), array('_account' => $account)));

    $access_check = $this->getMock('Drupal\Core\Access\AccessCheckInterface');
    $access_check->expects($this->any())
      ->method('applies')
      ->will($this->returnValue(TRUE));
    $access_check->expects($this->any())
      ->method('access')
      ->with($route, $subrequest)
      ->will($this->returnValue(AccessInterface::KILL));

    $subrequest->attributes->set('value', 'upcasted_value');
    $this->container->register('test_access', $access_check);

    $this->accessManager->addCheckService('test_access');
    $this->accessManager->setChecks($this->routeCollection);

    $this->assertFalse($this->accessManager->checkNamedRoute('test_route_1', array('value' => 'example')));
  }

  /**
   * Tests checkNamedRoute given an invalid/non existing route name.
   */
  public function testCheckNamedRouteWithNonExistingRoute() {
    $this->routeProvider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');

    $this->routeProvider->expects($this->any())
      ->method('getRouteByName')
      ->will($this->throwException(new RouteNotFoundException()));

    $this->setupAccessChecker();

    $this->assertFalse($this->accessManager->checkNamedRoute('test_route_1'), 'A non existing route lead to access.');
  }

  /**
   * Converts AccessCheckInterface constants to a string.
   *
   * @param mixed $constant
   *   The access constant which is tested, so either
   *   AccessCheckInterface::ALLOW, AccessCheckInterface::DENY OR
   *   AccessCheckInterface::KILL.
   *
   * @return string
   *   The corresponding string used in route requirements, so 'TRUE', 'FALSE'
   *   or 'NULL'.
   */
  protected static function convertAccessCheckInterfaceToString($constant) {
    if ($constant === AccessCheckInterface::ALLOW) {
      return 'TRUE';
    }
    if ($constant === AccessCheckInterface::DENY) {
      return 'NULL';
    }
    if ($constant === AccessCheckInterface::KILL) {
      return 'FALSE';
    }
  }

  /**
   * Adds a default access check service to the container and the access manager.
   */
  protected function setupAccessChecker() {
    $this->accessManager = new AccessManager($this->routeProvider, $this->urlGenerator, $this->paramConverter);
    $this->accessManager->setContainer($this->container);
    $access_check = new DefaultAccessCheck();
    $this->container->register('test_access_default', $access_check);
    $this->accessManager->addCheckService('test_access_default');
  }

}
