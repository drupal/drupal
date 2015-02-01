<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Access\AccessManagerTest.
 */

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Access\CheckProvider;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Access\AccessManager;
use Drupal\Core\Access\DefaultAccessCheck;
use Drupal\Tests\UnitTestCase;
use Drupal\router_test\Access\DefinedTestAccessCheck;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
   * @var \Drupal\Core\Access\AccessArgumentsResolverFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $argumentsResolverFactory;

  /**
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Access\CheckProvider
   */
  protected $checkProvider;

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

    $this->paramConverter = $this->getMock('Drupal\Core\ParamConverter\ParamConverterManagerInterface');

    $this->account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->currentUser = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->argumentsResolverFactory = $this->getMock('Drupal\Core\Access\AccessArgumentsResolverFactoryInterface');
    $this->checkProvider = new CheckProvider();
    $this->checkProvider->setContainer($this->container);

    $this->accessManager = new AccessManager($this->routeProvider, $this->paramConverter, $this->argumentsResolverFactory, $this->currentUser, $this->checkProvider);
  }

  /**
   * Tests \Drupal\Core\Access\AccessManager::setChecks().
   */
  public function testSetChecks() {
    // Check setChecks without any access checker defined yet.
    $this->checkProvider->setChecks($this->routeCollection);

    foreach ($this->routeCollection->all() as $route) {
      $this->assertNull($route->getOption('_access_checks'));
    }

    $this->setupAccessChecker();

    $this->checkProvider->setChecks($this->routeCollection);

    $this->assertEquals($this->routeCollection->get('test_route_1')->getOption('_access_checks'), NULL);
    $this->assertEquals($this->routeCollection->get('test_route_2')->getOption('_access_checks'), array('test_access_default'));
    $this->assertEquals($this->routeCollection->get('test_route_3')->getOption('_access_checks'), array('test_access_default'));
  }

  /**
   * Tests setChecks with a dynamic access checker.
   */
  public function testSetChecksWithDynamicAccessChecker() {
    // Setup the access manager.
    $this->accessManager = new AccessManager($this->routeProvider, $this->paramConverter, $this->argumentsResolverFactory, $this->currentUser, $this->checkProvider);

    // Setup the dynamic access checker.
    $access_check = $this->getMock('Drupal\Tests\Core\Access\TestAccessCheckInterface');
    $this->container->set('test_access', $access_check);
    $this->checkProvider->addCheckService('test_access', 'access');

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

    $this->checkProvider->setChecks($collection);
    $this->assertEmpty($route->getOption('_access_checks'));
    $this->assertEquals(array('test_access'), $route2->getOption('_access_checks'));
  }

  /**
   * Tests \Drupal\Core\Access\AccessManager::check().
   */
  public function testCheck() {
    $route_matches = [];

    // Construct route match objects.
    foreach ($this->routeCollection->all() as $route_name => $route) {
      $route_matches[$route_name] = new RouteMatch($route_name, $route, [], []);
    }

    // Check route access without any access checker defined yet.
    foreach ($route_matches as $route_match) {
      $this->assertEquals(FALSE, $this->accessManager->check($route_match, $this->account));
      $this->assertEquals(AccessResult::neutral(), $this->accessManager->check($route_match, $this->account, NULL, TRUE));
    }

    $this->setupAccessChecker();

    // An access checker got setup, but the routes haven't been setup using
    // setChecks.
    foreach ($route_matches as $route_match) {
      $this->assertEquals(FALSE, $this->accessManager->check($route_match, $this->account));
      $this->assertEquals(AccessResult::neutral(), $this->accessManager->check($route_match, $this->account, NULL, TRUE));
    }

    // Now applicable access checks have been saved on each route object.
    $this->checkProvider->setChecks($this->routeCollection);
    $this->setupAccessArgumentsResolverFactory();

    $this->assertEquals(FALSE, $this->accessManager->check($route_matches['test_route_1'], $this->account));
    $this->assertEquals(TRUE, $this->accessManager->check($route_matches['test_route_2'], $this->account));
    $this->assertEquals(FALSE, $this->accessManager->check($route_matches['test_route_3'], $this->account));
    $this->assertEquals(TRUE, $this->accessManager->check($route_matches['test_route_4'], $this->account));
    $this->assertEquals(AccessResult::neutral(), $this->accessManager->check($route_matches['test_route_1'], $this->account, NULL, TRUE));
    $this->assertEquals(AccessResult::allowed(), $this->accessManager->check($route_matches['test_route_2'], $this->account, NULL, TRUE));
    $this->assertEquals(AccessResult::forbidden(), $this->accessManager->check($route_matches['test_route_3'],  $this->account, NULL, TRUE));
    $this->assertEquals(AccessResult::allowed(), $this->accessManager->check($route_matches['test_route_4'],  $this->account, NULL, TRUE));
  }

  /**
   * Tests \Drupal\Core\Access\AccessManager::check() with no account specified.
   *
   * @covers ::check
   */
  public function testCheckWithNullAccount() {
    $this->setupAccessChecker();
    $this->checkProvider->setChecks($this->routeCollection);

    $route = $this->routeCollection->get('test_route_2');
    $route_match = new RouteMatch('test_route_2', $route, [], []);

    // Asserts that the current user is passed to the access arguments resolver
    // factory.
    $this->setupAccessArgumentsResolverFactory()
      ->with($route_match, $this->currentUser, NULL);

    $this->assertTrue($this->accessManager->check($route_match));
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
    $access_allow = AccessResult::allowed();
    $access_deny = AccessResult::neutral();
    $access_kill = AccessResult::forbidden();

    $access_configurations = array();
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ALL,
      'name' => 'test_route_4',
      'condition_one' => 'TRUE',
      'condition_two' => 'FALSE',
      'expected' => $access_kill,
    );
    $access_configurations[] = array(
      'conjunction' => NULL,
      'name' => 'test_route_4',
      'condition_one' => 'TRUE',
      'condition_two' => 'FALSE',
      'expected' => $access_kill,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ALL,
      'name' => 'test_route_5',
      'condition_one' => 'TRUE',
      'condition_two' => 'NULL',
      'expected' => $access_deny,
    );
    $access_configurations[] = array(
      'conjunction' => NULL,
      'name' => 'test_route_5',
      'condition_one' => 'TRUE',
      'condition_two' => 'NULL',
      'expected' => $access_deny,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ALL,
      'name' => 'test_route_6',
      'condition_one' => 'FALSE',
      'condition_two' => 'NULL',
      'expected' => $access_kill,
    );
    $access_configurations[] = array(
      'conjunction' => NULL,
      'name' => 'test_route_6',
      'condition_one' => 'FALSE',
      'condition_two' => 'NULL',
      'expected' => $access_kill,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ALL,
      'name' => 'test_route_7',
      'condition_one' => 'TRUE',
      'condition_two' => 'TRUE',
      'expected' => $access_allow,
    );
    $access_configurations[] = array(
      'conjunction' => NULL,
      'name' => 'test_route_7',
      'condition_one' => 'TRUE',
      'condition_two' => 'TRUE',
      'expected' => $access_allow,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ALL,
      'name' => 'test_route_8',
      'condition_one' => 'FALSE',
      'condition_two' => 'FALSE',
      'expected' => $access_kill,
    );
    $access_configurations[] = array(
      'conjunction' => NULL,
      'name' => 'test_route_8',
      'condition_one' => 'FALSE',
      'condition_two' => 'FALSE',
      'expected' => $access_kill,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ALL,
      'name' => 'test_route_9',
      'condition_one' => 'NULL',
      'condition_two' => 'NULL',
      'expected' => $access_deny,
    );
    $access_configurations[] = array(
      'conjunction' => NULL,
      'name' => 'test_route_9',
      'condition_one' => 'NULL',
      'condition_two' => 'NULL',
      'expected' => $access_deny,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ANY,
      'name' => 'test_route_10',
      'condition_one' => 'TRUE',
      'condition_two' => 'FALSE',
      'expected' => $access_kill,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ANY,
      'name' => 'test_route_11',
      'condition_one' => 'TRUE',
      'condition_two' => 'NULL',
      'expected' => $access_allow,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ANY,
      'name' => 'test_route_12',
      'condition_one' => 'FALSE',
      'condition_two' => 'NULL',
      'expected' => $access_kill,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ANY,
      'name' => 'test_route_13',
      'condition_one' => 'TRUE',
      'condition_two' => 'TRUE',
      'expected' => $access_allow,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ANY,
      'name' => 'test_route_14',
      'condition_one' => 'FALSE',
      'condition_two' => 'FALSE',
      'expected' => $access_kill,
    );
    $access_configurations[] = array(
      'conjunction' => AccessManagerInterface::ACCESS_MODE_ANY,
      'name' => 'test_route_15',
      'condition_one' => 'NULL',
      'condition_two' => 'NULL',
      'expected' => $access_deny,
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
    $this->checkProvider->addCheckService('test_access_defined', 'access', array('_test_access'));

    $route_collection = new RouteCollection();
    // Setup a test route for each access configuration.
    $requirements = array(
      '_access' => $condition_one,
      '_test_access' => $condition_two,
    );
    $options = $conjunction ? array('_access_mode' => $conjunction) : array();
    $route = new Route($name, array(), $requirements, $options);
    $route_collection->add($name, $route);

    $this->checkProvider->setChecks($route_collection);
    $this->setupAccessArgumentsResolverFactory();

    $route_match = new RouteMatch($name, $route, array(), array());
    $this->assertEquals($expected_access->isAllowed(), $this->accessManager->check($route_match, $this->account));
    $this->assertEquals($expected_access, $this->accessManager->check($route_match, $this->account, NULL, TRUE));
  }

  /**
   * Tests the checkNamedRoute method.
   *
   * @see \Drupal\Core\Access\AccessManager::checkNamedRoute()
   */
  public function testCheckNamedRoute() {
    $this->setupAccessChecker();
    $this->checkProvider->setChecks($this->routeCollection);
    $this->setupAccessArgumentsResolverFactory();

    $this->paramConverter->expects($this->at(0))
      ->method('convert')
      ->with(array(RouteObjectInterface::ROUTE_NAME => 'test_route_2', RouteObjectInterface::ROUTE_OBJECT => $this->routeCollection->get('test_route_2')))
      ->will($this->returnValue(array()));
    $this->paramConverter->expects($this->at(1))
      ->method('convert')
      ->with(array(RouteObjectInterface::ROUTE_NAME => 'test_route_2', RouteObjectInterface::ROUTE_OBJECT => $this->routeCollection->get('test_route_2')))
      ->will($this->returnValue(array()));

    $this->paramConverter->expects($this->at(2))
      ->method('convert')
      ->with(array('value' => 'example', RouteObjectInterface::ROUTE_NAME => 'test_route_4', RouteObjectInterface::ROUTE_OBJECT => $this->routeCollection->get('test_route_4')))
      ->will($this->returnValue(array('value' => 'example')));
    $this->paramConverter->expects($this->at(3))
      ->method('convert')
      ->with(array('value' => 'example', RouteObjectInterface::ROUTE_NAME => 'test_route_4', RouteObjectInterface::ROUTE_OBJECT => $this->routeCollection->get('test_route_4')))
      ->will($this->returnValue(array('value' => 'example')));

    // Tests the access with routes with parameters without given request.
    $this->assertEquals(TRUE, $this->accessManager->checkNamedRoute('test_route_2', array(), $this->account));
    $this->assertEquals(AccessResult::allowed(), $this->accessManager->checkNamedRoute('test_route_2', array(), $this->account, TRUE));
    $this->assertEquals(TRUE, $this->accessManager->checkNamedRoute('test_route_4', array('value' => 'example'), $this->account));
    $this->assertEquals(AccessResult::allowed(), $this->accessManager->checkNamedRoute('test_route_4', array('value' => 'example'), $this->account, TRUE));
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

    $this->paramConverter = $this->getMock('Drupal\Core\ParamConverter\ParamConverterManagerInterface');
    $this->paramConverter->expects($this->atLeastOnce())
      ->method('convert')
      ->with(array('value' => 'example', RouteObjectInterface::ROUTE_NAME => 'test_route_1', RouteObjectInterface::ROUTE_OBJECT => $route))
      ->will($this->returnValue(array('value' => 'upcasted_value')));

    $this->setupAccessArgumentsResolverFactory($this->exactly(2))
      ->with($this->callback(function ($route_match) {
        return $route_match->getParameters()->get('value') == 'upcasted_value';
      }));

    $this->accessManager = new AccessManager($this->routeProvider, $this->paramConverter, $this->argumentsResolverFactory, $this->currentUser, $this->checkProvider);

    $access_check = $this->getMock('Drupal\Tests\Core\Access\TestAccessCheckInterface');
    $access_check->expects($this->atLeastOnce())
      ->method('applies')
      ->will($this->returnValue(TRUE));
    $access_check->expects($this->atLeastOnce())
      ->method('access')
      ->will($this->returnValue(AccessResult::forbidden()));

    $this->container->set('test_access', $access_check);

    $this->checkProvider->addCheckService('test_access', 'access');
    $this->checkProvider->setChecks($this->routeCollection);

    $this->assertEquals(FALSE, $this->accessManager->checkNamedRoute('test_route_1', array('value' => 'example'), $this->account));
    $this->assertEquals(AccessResult::forbidden(), $this->accessManager->checkNamedRoute('test_route_1', array('value' => 'example'), $this->account, TRUE));
  }

  /**
   * Tests the checkNamedRoute with default values.
   *
   * @covers \Drupal\Core\Access\AccessManager::checkNamedRoute
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

    $this->paramConverter = $this->getMock('Drupal\Core\ParamConverter\ParamConverterManagerInterface');
    $this->paramConverter->expects($this->atLeastOnce())
      ->method('convert')
      ->with(array('value' => 'example', RouteObjectInterface::ROUTE_NAME => 'test_route_1', RouteObjectInterface::ROUTE_OBJECT => $route))
      ->will($this->returnValue(array('value' => 'upcasted_value')));

    $this->setupAccessArgumentsResolverFactory($this->exactly(2))
      ->with($this->callback(function ($route_match) {
        return $route_match->getParameters()->get('value') == 'upcasted_value';
      }));

    $this->accessManager = new AccessManager($this->routeProvider, $this->paramConverter, $this->argumentsResolverFactory, $this->currentUser, $this->checkProvider);

    $access_check = $this->getMock('Drupal\Tests\Core\Access\TestAccessCheckInterface');
    $access_check->expects($this->atLeastOnce())
      ->method('applies')
      ->will($this->returnValue(TRUE));
    $access_check->expects($this->atLeastOnce())
      ->method('access')
      ->will($this->returnValue(AccessResult::forbidden()));

    $this->container->set('test_access', $access_check);

    $this->checkProvider->addCheckService('test_access', 'access');
    $this->checkProvider->setChecks($this->routeCollection);

    $this->assertEquals(FALSE, $this->accessManager->checkNamedRoute('test_route_1', array(), $this->account));
    $this->assertEquals(AccessResult::forbidden(), $this->accessManager->checkNamedRoute('test_route_1', array(), $this->account, TRUE));
  }

  /**
   * Tests checkNamedRoute given an invalid/non existing route name.
   */
  public function testCheckNamedRouteWithNonExistingRoute() {
    $this->routeProvider->expects($this->any())
      ->method('getRouteByName')
      ->will($this->throwException(new RouteNotFoundException()));

    $this->setupAccessChecker();

    $this->assertEquals(FALSE, $this->accessManager->checkNamedRoute('test_route_1', array(), $this->account), 'A non existing route lead to access.');
    $this->assertEquals(AccessResult::forbidden()->addCacheTags(['config:core.extension']), $this->accessManager->checkNamedRoute('test_route_1', array(), $this->account, TRUE), 'A non existing route lead to access.');
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

    $this->paramConverter = $this->getMock('Drupal\Core\ParamConverter\ParamConverterManagerInterface');
    $this->paramConverter->expects($this->any())
      ->method('convert')
      ->will($this->returnValue(array()));

    $this->setupAccessArgumentsResolverFactory();

    $container = new ContainerBuilder();

    // Register a service that will return an incorrect value.
    $access_check = $this->getMock('Drupal\Tests\Core\Access\TestAccessCheckInterface');
    $access_check->expects($this->any())
      ->method('access')
      ->will($this->returnValue($return_value));
    $container->set('test_incorrect_value', $access_check);

    $access_manager = new AccessManager($route_provider, $this->paramConverter, $this->argumentsResolverFactory, $this->currentUser, $this->checkProvider);
    $this->checkProvider->setContainer($container);
    $this->checkProvider->addCheckService('test_incorrect_value', 'access');

    $access_manager->checkNamedRoute('test_incorrect_value', array(), $this->account);
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
   * Adds a default access check service to the container and the access manager.
   */
  protected function setupAccessChecker() {
    $access_check = new DefaultAccessCheck();
    $this->container->register('test_access_default', $access_check);
    $this->checkProvider->addCheckService('test_access_default', 'access', array('_access'));
  }

  /**
   * Add default expectations to the access arguments resolver factory.
   */
  protected function setupAccessArgumentsResolverFactory($constraint = NULL) {
    if (!isset($constraint)) {
      $constraint = $this->any();
    }
    return $this->argumentsResolverFactory->expects($constraint)
      ->method('getArgumentsResolver')
      ->will($this->returnCallback(function ($route_match, $account) {
        $resolver = $this->getMock('Drupal\Component\Utility\ArgumentsResolverInterface');
        $resolver->expects($this->any())
          ->method('getArguments')
          ->will($this->returnCallback(function ($callable) use ($route_match) {
            return array($route_match->getRouteObject());
          }));
        return $resolver;
      }));
  }

}

/**
 * Defines an interface with a defined access() method for mocking.
 */
interface TestAccessCheckInterface extends AccessCheckInterface {
  public function access();
}
