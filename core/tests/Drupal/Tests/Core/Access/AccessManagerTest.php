<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Access\AccessManagerTest.
 */

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessManager;
use Drupal\Core\Access\DefaultAccessCheck;
use Drupal\Tests\UnitTestCase;
use Drupal\router_test\Access\DefinedTestAccessCheck;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\Core\Access\AccessManager
 * @group Access
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
   * @var \Drupal\Core\ParamConverter\ParamConverterManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $paramConverter;

  /**
   * The mocked account.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $account;

  /**
   * The access arguments resolver.
   *
   * @var \Drupal\Core\Access\AccessArgumentsResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $argumentsResolver;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

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

    $this->paramConverter = $this->getMock('Drupal\Core\ParamConverter\ParamConverterManagerInterface');

    $this->account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->argumentsResolver = $this->getMock('Drupal\Core\Access\AccessArgumentsResolverInterface');

    $this->requestStack = new RequestStack();

    $this->accessManager = new AccessManager($this->routeProvider, $this->urlGenerator, $this->paramConverter, $this->argumentsResolver, $this->requestStack);
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
   * Tests setChecks with a dynamic access checker.
   */
  public function testSetChecksWithDynamicAccessChecker() {
    // Setup the access manager.
    $this->accessManager = new AccessManager($this->routeProvider, $this->urlGenerator, $this->paramConverter, $this->argumentsResolver, $this->requestStack);
    $this->accessManager->setContainer($this->container);

    // Setup the dynamic access checker.
    $access_check = $this->getMock('Drupal\Tests\Core\Access\TestAccessCheckInterface');
    $this->container->set('test_access', $access_check);
    $this->accessManager->addCheckService('test_access', 'access');

    $route = new Route('/test-path', array(), array('_foo' => '1', '_bar' => '1'));
    $route2 = new Route('/test-path', array(), array('_foo' => '1', '_bar' => '2'));
    $collection = new RouteCollection();
    $collection->add('test_route', $route);
    $collection->add('test_route2', $route2);

    $access_check->expects($this->exactly(2))
      ->method('applies')
      ->with($this->isInstanceOf('Symfony\Component\Routing\Route'))
      ->will($this->returnCallback(function (Route $route) {
         return $route->getRequirement('_bar') == 2;
      }));

    $this->accessManager->setChecks($collection);
    $this->assertEmpty($route->getOption('_access_checks'));
    $this->assertEquals(array('test_access'), $route2->getOption('_access_checks'));
  }

  /**
   * Tests \Drupal\Core\Access\AccessManager::check().
   */
  public function testCheck() {
    $request = new Request();

    // Check check without any access checker defined yet.
    foreach ($this->routeCollection->all() as $route) {
      $this->assertFalse($this->accessManager->check($route, $request, $this->account));
    }

    $this->setupAccessChecker();

    // An access checker got setup, but the routes haven't been setup using
    // setChecks.
    foreach ($this->routeCollection->all() as $route) {
      $this->assertFalse($this->accessManager->check($route, $request, $this->account));
    }

    $this->accessManager->setChecks($this->routeCollection);
    $this->argumentsResolver->expects($this->any())
      ->method('getArguments')
      ->will($this->returnCallback(function ($callable, $route, $request, $account) {
        return array($route);
      }));

    $this->assertFalse($this->accessManager->check($this->routeCollection->get('test_route_1'), $request, $this->account));
    $this->assertTrue($this->accessManager->check($this->routeCollection->get('test_route_2'), $request, $this->account));
    $this->assertFalse($this->accessManager->check($this->routeCollection->get('test_route_3'), $request, $this->account));
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
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ALL,
      'name' => 'test_route_4',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::KILL,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => NULL,
      'name' => 'test_route_4',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::KILL,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ALL,
      'name' => 'test_route_5',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::DENY,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => NULL,
      'name' => 'test_route_5',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::DENY,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ALL,
      'name' => 'test_route_6',
      'condition_one' => AccessCheckInterface::KILL,
      'condition_two' => AccessCheckInterface::DENY,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => NULL,
      'name' => 'test_route_6',
      'condition_one' => AccessCheckInterface::KILL,
      'condition_two' => AccessCheckInterface::DENY,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ALL,
      'name' => 'test_route_7',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::ALLOW,
      'expected' => TRUE,
    );
    $access_configurations[] = array(
      'conjunction' => NULL,
      'name' => 'test_route_7',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::ALLOW,
      'expected' => TRUE,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ALL,
      'name' => 'test_route_8',
      'condition_one' => AccessCheckInterface::KILL,
      'condition_two' => AccessCheckInterface::KILL,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => NULL,
      'name' => 'test_route_8',
      'condition_one' => AccessCheckInterface::KILL,
      'condition_two' => AccessCheckInterface::KILL,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ALL,
      'name' => 'test_route_9',
      'condition_one' => AccessCheckInterface::DENY,
      'condition_two' => AccessCheckInterface::DENY,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => NULL,
      'name' => 'test_route_9',
      'condition_one' => AccessCheckInterface::DENY,
      'condition_two' => AccessCheckInterface::DENY,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ANY,
      'name' => 'test_route_10',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::KILL,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ANY,
      'name' => 'test_route_11',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::DENY,
      'expected' => TRUE,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ANY,
      'name' => 'test_route_12',
      'condition_one' => AccessCheckInterface::KILL,
      'condition_two' => AccessCheckInterface::DENY,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ANY,
      'name' => 'test_route_13',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::ALLOW,
      'expected' => TRUE,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ANY,
      'name' => 'test_route_14',
      'condition_one' => AccessCheckInterface::KILL,
      'condition_two' => AccessCheckInterface::KILL,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ANY,
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
    $this->accessManager->addCheckService('test_access_defined', 'access', array('_test_access'));

    $request = new Request();

    $route_collection = new RouteCollection();
    // Setup a test route for each access configuration.
    $requirements = array(
      '_access' => static::convertAccessCheckInterfaceToString($condition_one),
      '_test_access' => static::convertAccessCheckInterfaceToString($condition_two),
    );
    $options = $conjunction ? array('_access_mode' => $conjunction) : array();
    $route = new Route($name, array(), $requirements, $options);
    $route_collection->add($name, $route);
    $this->argumentsResolver->expects($this->any())
      ->method('getArguments')
      ->will($this->returnCallback(function ($callable, $route, $request, $account) {
        return array($route, $request, $account);
      }));

    $this->accessManager->setChecks($route_collection);
    $this->assertSame($this->accessManager->check($route, $request, $this->account), $expected_access);
  }

  /**
   * Tests the checkNamedRoute method.
   *
   * @see \Drupal\Core\Access\AccessManager::checkNamedRoute()
   */
  public function testCheckNamedRoute() {
    $this->setupAccessChecker();
    $this->accessManager->setChecks($this->routeCollection);
    $this->argumentsResolver->expects($this->any())
      ->method('getArguments')
      ->will($this->returnCallback(function ($callable, $route, $request, $account) {
        return array($route, $request, $account);
      }));

    // Tests the access with routes without parameters.
    $request = new Request();
    $this->assertTrue($this->accessManager->checkNamedRoute('test_route_2', array(), $this->account, $request));
    $this->assertFalse($this->accessManager->checkNamedRoute('test_route_3', array(), $this->account, $request));

    // Tests the access with routes with parameters with given request.
    $request = new Request();
    $request->attributes->set('value', 'example');
    $request->attributes->set('value2', 'example2');
    $this->assertTrue($this->accessManager->checkNamedRoute('test_route_4', array(), $this->account, $request));

    // Tests the access with routes without given request.
    $this->requestStack->push(new Request());

    $this->paramConverter->expects($this->at(0))
      ->method('convert')
      ->with(array(RouteObjectInterface::ROUTE_OBJECT => $this->routeCollection->get('test_route_2')))
      ->will($this->returnValue(array()));

    $this->paramConverter->expects($this->at(1))
      ->method('convert')
      ->with(array('value' => 'example', RouteObjectInterface::ROUTE_OBJECT => $this->routeCollection->get('test_route_4')))
      ->will($this->returnValue(array('value' => 'example')));

    // Tests the access with routes with parameters without given request.
    $this->assertTrue($this->accessManager->checkNamedRoute('test_route_2', array(), $this->account));
    $this->assertTrue($this->accessManager->checkNamedRoute('test_route_4', array('value' => 'example'), $this->account));
  }

  /**
   * Tests the checkNamedRoute with upcasted values.
   *
   * @see \Drupal\Core\Access\AccessManager::checkNamedRoute()
   */
  public function testCheckNamedRouteWithUpcastedValues() {
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

    $this->paramConverter = $this->getMock('Drupal\Core\ParamConverter\ParamConverterManagerInterface');
    $this->paramConverter->expects($this->at(0))
      ->method('convert')
      ->with(array('value' => 'example', RouteObjectInterface::ROUTE_OBJECT => $route))
      ->will($this->returnValue(array('value' => 'upcasted_value')));

    $this->argumentsResolver->expects($this->atLeastOnce())
      ->method('getArguments')
      ->will($this->returnCallback(function ($callable, $route, $request, $account) {
        return array($route);
      }));

    $subrequest = Request::create('/test-route-1/example');

    $this->accessManager = new AccessManager($this->routeProvider, $this->urlGenerator, $this->paramConverter, $this->argumentsResolver, $this->requestStack);
    $this->accessManager->setContainer($this->container);
    $this->requestStack->push(new Request());

    $access_check = $this->getMock('Drupal\Tests\Core\Access\TestAccessCheckInterface');
    $access_check->expects($this->atLeastOnce())
      ->method('applies')
      ->will($this->returnValue(TRUE));
    $access_check->expects($this->atLeastOnce())
      ->method('access')
      ->will($this->returnValue(AccessInterface::KILL));

    $subrequest->attributes->set('value', 'upcasted_value');
    $this->container->set('test_access', $access_check);

    $this->accessManager->addCheckService('test_access', 'access');
    $this->accessManager->setChecks($this->routeCollection);

    $this->assertFalse($this->accessManager->checkNamedRoute('test_route_1', array('value' => 'example'), $this->account));
  }

    /**
   * Tests the checkNamedRoute with default values.
   *
   * @covers \Drupal\Core\Access\AccessManager::checkNamedRoute()
   */
  public function testCheckNamedRouteWithDefaultValue() {
    $this->routeCollection = new RouteCollection();
    $route = new Route('/test-route-1/{value}', array('value' => 'example'), array('_test_access' => 'TRUE'));
    $this->routeCollection->add('test_route_1', $route);

    $this->routeProvider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');
    $this->routeProvider->expects($this->any())
      ->method('getRouteByName')
      ->with('test_route_1', array())
      ->will($this->returnValue($route));

    $map = array();
    $map[] = array('test_route_1', array('value' => 'example'), '/test-route-1/example');

    $this->urlGenerator = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
    $this->urlGenerator->expects($this->any())
      ->method('generate')
      ->with('test_route_1', array())
      ->will($this->returnValueMap($map));

    $this->paramConverter = $this->getMock('Drupal\Core\ParamConverter\ParamConverterManagerInterface');
    $this->paramConverter->expects($this->at(0))
      ->method('convert')
      ->with(array('value' => 'example', RouteObjectInterface::ROUTE_OBJECT => $route))
      ->will($this->returnValue(array('value' => 'upcasted_value')));

    $this->argumentsResolver->expects($this->atLeastOnce())
      ->method('getArguments')
      ->will($this->returnCallback(function ($callable, $route, $request, $account) {
        return array($route);
      }));

    $subrequest = Request::create('/test-route-1/example');

    $this->accessManager = new AccessManager($this->routeProvider, $this->urlGenerator, $this->paramConverter, $this->argumentsResolver, $this->requestStack);
    $this->accessManager->setContainer($this->container);
    $this->requestStack->push(new Request());

    $access_check = $this->getMock('Drupal\Tests\Core\Access\TestAccessCheckInterface');
    $access_check->expects($this->atLeastOnce())
      ->method('applies')
      ->will($this->returnValue(TRUE));
    $access_check->expects($this->atLeastOnce())
      ->method('access')
      ->will($this->returnValue(AccessInterface::KILL));

    $subrequest->attributes->set('value', 'upcasted_value');
    $this->container->set('test_access', $access_check);

    $this->accessManager->addCheckService('test_access', 'access');
    $this->accessManager->setChecks($this->routeCollection);

    $this->assertFalse($this->accessManager->checkNamedRoute('test_route_1', array(), $this->account));
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

    $this->assertFalse($this->accessManager->checkNamedRoute('test_route_1', array(), $this->account), 'A non existing route lead to access.');
  }

  /**
   * Tests that an access checker throws an exception for not allowed values.
   *
   * @dataProvider providerCheckException
   *
   * @expectedException \Drupal\Core\Access\AccessException
   */
  public function testCheckException($return_value, $access_mode) {
    $route_provider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');

    // Setup a test route for each access configuration.
    $requirements = array(
      '_test_incorrect_value' => 'TRUE',
    );
    $options = array(
      '_access_mode' => $access_mode,
      '_access_checks' => array(
        'test_incorrect_value',
      ),
    );
    $route = new Route('', array(), $requirements, $options);

    $route_provider->expects($this->any())
      ->method('getRouteByName')
      ->will($this->returnValue($route));
    $this->argumentsResolver->expects($this->any())
      ->method('getArguments')
      ->will($this->returnCallback(function ($callable, $route, $request, $account) {
        return array($route);
      }));

    $request = new Request();

    $container = new ContainerBuilder();

    // Register a service that will return an incorrect value.
    $access_check = $this->getMock('Drupal\Tests\Core\Access\TestAccessCheckInterface');
    $access_check->expects($this->any())
      ->method('access')
      ->will($this->returnValue($return_value));
    $container->set('test_incorrect_value', $access_check);

    $access_manager = new AccessManager($route_provider, $this->urlGenerator, $this->paramConverter, $this->argumentsResolver, $this->requestStack);
    $access_manager->setContainer($container);
    $access_manager->addCheckService('test_incorrect_value', 'access');

    $access_manager->checkNamedRoute('test_incorrect_value', array(), $this->account, $request);
  }

  /**
   * Data provider for testCheckException.
   *
   * @return array
   */
  public function providerCheckException() {
    return array(
      array(array(), AccessManagerInterface::ACCESS_MODE_ALL),
      array(array(), AccessManagerInterface::ACCESS_MODE_ANY),
      array(array(1), AccessManagerInterface::ACCESS_MODE_ALL),
      array(array(1), AccessManagerInterface::ACCESS_MODE_ANY),
      array('string', AccessManagerInterface::ACCESS_MODE_ALL),
      array('string', AccessManagerInterface::ACCESS_MODE_ANY),
      array(0, AccessManagerInterface::ACCESS_MODE_ALL),
      array(0, AccessManagerInterface::ACCESS_MODE_ANY),
      array(1, AccessManagerInterface::ACCESS_MODE_ALL),
      array(1, AccessManagerInterface::ACCESS_MODE_ANY),
    );
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
    $this->accessManager = new AccessManager($this->routeProvider, $this->urlGenerator, $this->paramConverter, $this->argumentsResolver, $this->requestStack);
    $this->accessManager->setContainer($this->container);
    $access_check = new DefaultAccessCheck();
    $this->container->register('test_access_default', $access_check);
    $this->accessManager->addCheckService('test_access_default', 'access', array('_access'));
  }

}

/**
 * Defines an interface with a defined access() method for mocking.
 */
interface TestAccessCheckInterface extends AccessCheckInterface {
  public function access();
}
