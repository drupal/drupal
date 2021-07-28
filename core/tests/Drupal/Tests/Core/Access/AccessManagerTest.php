<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Access\AccessManagerTest.
 */

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\CheckProvider;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Access\AccessManager;
use Drupal\Core\Access\DefaultAccessCheck;
use Drupal\Tests\UnitTestCase;
use Drupal\router_test\Access\DefinedTestAccessCheck;
use Drupal\Core\Routing\RouteObjectInterface;
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
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $routeProvider;

  /**
   * The parameter converter.
   *
   * @var \Drupal\Core\ParamConverter\ParamConverterManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paramConverter;

  /**
   * The mocked account.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $account;

  /**
   * The access arguments resolver.
   *
   * @var \Drupal\Core\Access\AccessArgumentsResolverFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $argumentsResolverFactory;

  /**
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Access\CheckProvider
   */
  protected $checkProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class)->reveal();
    $this->container->set('cache_contexts_manager', $cache_contexts_manager);
    $this->container->setParameter('dynamic_access_check_services', []);
    \Drupal::setContainer($this->container);

    $this->routeCollection = new RouteCollection();
    $this->routeCollection->add('test_route_1', new Route('/test-route-1'));
    $this->routeCollection->add('test_route_2', new Route('/test-route-2', [], ['_access' => 'TRUE']));
    $this->routeCollection->add('test_route_3', new Route('/test-route-3', [], ['_access' => 'FALSE']));
    $this->routeCollection->add('test_route_4', new Route('/test-route-4/{value}', [], ['_access' => 'TRUE']));

    $this->routeProvider = $this->createMock('Drupal\Core\Routing\RouteProviderInterface');
    $map = [];
    foreach ($this->routeCollection->all() as $name => $route) {
      $map[] = [$name, $route];
    }
    $map[] = ['test_route_4', $this->routeCollection->get('test_route_4')];
    $this->routeProvider->expects($this->any())
      ->method('getRouteByName')
      ->willReturnMap($map);

    $map = [];
    $map[] = ['test_route_1', [], '/test-route-1'];
    $map[] = ['test_route_2', [], '/test-route-2'];
    $map[] = ['test_route_3', [], '/test-route-3'];
    $map[] = ['test_route_4', ['value' => 'example'], '/test-route-4/example'];

    $this->paramConverter = $this->createMock('Drupal\Core\ParamConverter\ParamConverterManagerInterface');

    $this->account = $this->createMock('Drupal\Core\Session\AccountInterface');
    $this->currentUser = $this->createMock('Drupal\Core\Session\AccountInterface');
    $this->argumentsResolverFactory = $this->createMock('Drupal\Core\Access\AccessArgumentsResolverFactoryInterface');
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

    $this->assertNull($this->routeCollection->get('test_route_1')->getOption('_access_checks'));
    $this->assertEquals(['test_access_default'], $this->routeCollection->get('test_route_2')->getOption('_access_checks'));
    $this->assertEquals(['test_access_default'], $this->routeCollection->get('test_route_3')->getOption('_access_checks'));
  }

  /**
   * Tests setChecks with a dynamic access checker.
   */
  public function testSetChecksWithDynamicAccessChecker() {
    // Setup the access manager.
    $this->accessManager = new AccessManager($this->routeProvider, $this->paramConverter, $this->argumentsResolverFactory, $this->currentUser, $this->checkProvider);

    // Setup the dynamic access checker.
    $access_check = $this->createMock('Drupal\Tests\Core\Access\TestAccessCheckInterface');
    $this->container->set('test_access', $access_check);
    $this->container->setParameter('dynamic_access_check_services', ['test_access']);
    $this->checkProvider->addCheckService('test_access', 'access');

    $route = new Route('/test-path', [], ['_foo' => '1', '_bar' => '1']);
    $route2 = new Route('/test-path', [], ['_foo' => '1', '_bar' => '2']);
    $collection = new RouteCollection();
    $collection->add('test_route', $route);
    $collection->add('test_route2', $route2);

    $access_check->expects($this->exactly(2))
      ->method('applies')
      ->with($this->isInstanceOf('Symfony\Component\Routing\Route'))
      ->willReturnCallback(function (Route $route) {
        return $route->getRequirement('_bar') == 2;
      });

    $this->checkProvider->setChecks($collection);
    $this->assertEmpty($route->getOption('_access_checks'));
    $this->assertEquals(['test_access'], $route2->getOption('_access_checks'));
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
    $this->assertEquals(AccessResult::forbidden(), $this->accessManager->check($route_matches['test_route_3'], $this->account, NULL, TRUE));
    $this->assertEquals(AccessResult::allowed(), $this->accessManager->check($route_matches['test_route_4'], $this->account, NULL, TRUE));
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

    $access_configurations = [];
    $access_configurations[] = [
      'name' => 'test_route_4',
      'condition_one' => 'TRUE',
      'condition_two' => 'FALSE',
      'expected' => $access_kill,
    ];
    $access_configurations[] = [
      'name' => 'test_route_5',
      'condition_one' => 'TRUE',
      'condition_two' => 'NULL',
      'expected' => $access_deny,
    ];
    $access_configurations[] = [
      'name' => 'test_route_6',
      'condition_one' => 'FALSE',
      'condition_two' => 'NULL',
      'expected' => $access_kill,
    ];
    $access_configurations[] = [
      'name' => 'test_route_7',
      'condition_one' => 'TRUE',
      'condition_two' => 'TRUE',
      'expected' => $access_allow,
    ];
    $access_configurations[] = [
      'name' => 'test_route_8',
      'condition_one' => 'FALSE',
      'condition_two' => 'FALSE',
      'expected' => $access_kill,
    ];
    $access_configurations[] = [
      'name' => 'test_route_9',
      'condition_one' => 'NULL',
      'condition_two' => 'NULL',
      'expected' => $access_deny,
    ];

    return $access_configurations;
  }

  /**
   * Tests \Drupal\Core\Access\AccessManager::check() with conjunctions.
   *
   * @dataProvider providerTestCheckConjunctions
   */
  public function testCheckConjunctions($name, $condition_one, $condition_two, $expected_access) {
    $this->setupAccessChecker();
    $this->container->register('test_access_defined', DefinedTestAccessCheck::class);
    $this->checkProvider->addCheckService('test_access_defined', 'access', ['_test_access']);

    $route_collection = new RouteCollection();
    // Setup a test route for each access configuration.
    $requirements = [
      '_access' => $condition_one,
      '_test_access' => $condition_two,
    ];
    $route = new Route($name, [], $requirements);
    $route_collection->add($name, $route);

    $this->checkProvider->setChecks($route_collection);
    $this->setupAccessArgumentsResolverFactory();

    $route_match = new RouteMatch($name, $route, [], []);
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

    $this->paramConverter->expects($this->exactly(4))
      ->method('convert')
      ->willReturnMap([
        [[RouteObjectInterface::ROUTE_NAME => 'test_route_2', RouteObjectInterface::ROUTE_OBJECT => $this->routeCollection->get('test_route_2')], []],
        [['value' => 'example', RouteObjectInterface::ROUTE_NAME => 'test_route_4', RouteObjectInterface::ROUTE_OBJECT => $this->routeCollection->get('test_route_4')], ['value' => 'example']],
      ]);

    // Tests the access with routes with parameters without given request.
    $this->assertEquals(TRUE, $this->accessManager->checkNamedRoute('test_route_2', [], $this->account));
    $this->assertEquals(AccessResult::allowed(), $this->accessManager->checkNamedRoute('test_route_2', [], $this->account, TRUE));
    $this->assertEquals(TRUE, $this->accessManager->checkNamedRoute('test_route_4', ['value' => 'example'], $this->account));
    $this->assertEquals(AccessResult::allowed(), $this->accessManager->checkNamedRoute('test_route_4', ['value' => 'example'], $this->account, TRUE));
  }

  /**
   * Tests the checkNamedRoute with upcasted values.
   *
   * @see \Drupal\Core\Access\AccessManager::checkNamedRoute()
   */
  public function testCheckNamedRouteWithUpcastedValues() {
    $this->routeCollection = new RouteCollection();
    $route = new Route('/test-route-1/{value}', [], ['_test_access' => 'TRUE']);
    $this->routeCollection->add('test_route_1', $route);

    $this->routeProvider = $this->createMock('Drupal\Core\Routing\RouteProviderInterface');
    $this->routeProvider->expects($this->any())
      ->method('getRouteByName')
      ->with('test_route_1')
      ->will($this->returnValue($route));

    $map[] = ['test_route_1', ['value' => 'example'], '/test-route-1/example'];

    $this->paramConverter = $this->createMock('Drupal\Core\ParamConverter\ParamConverterManagerInterface');
    $this->paramConverter->expects($this->atLeastOnce())
      ->method('convert')
      ->with(['value' => 'example', RouteObjectInterface::ROUTE_NAME => 'test_route_1', RouteObjectInterface::ROUTE_OBJECT => $route])
      ->will($this->returnValue(['value' => 'upcasted_value']));

    $this->setupAccessArgumentsResolverFactory($this->exactly(2))
      ->with($this->callback(function ($route_match) {
        return $route_match->getParameters()->get('value') == 'upcasted_value';
      }));

    $this->accessManager = new AccessManager($this->routeProvider, $this->paramConverter, $this->argumentsResolverFactory, $this->currentUser, $this->checkProvider);

    $access_check = $this->createMock('Drupal\Tests\Core\Access\TestAccessCheckInterface');
    $access_check->expects($this->atLeastOnce())
      ->method('applies')
      ->will($this->returnValue(TRUE));
    $access_check->expects($this->atLeastOnce())
      ->method('access')
      ->will($this->returnValue(AccessResult::forbidden()));

    $this->container->set('test_access', $access_check);
    $this->container->setParameter('dynamic_access_check_services', ['test_access']);

    $this->checkProvider->addCheckService('test_access', 'access');
    $this->checkProvider->setChecks($this->routeCollection);

    $this->assertEquals(FALSE, $this->accessManager->checkNamedRoute('test_route_1', ['value' => 'example'], $this->account));
    $this->assertEquals(AccessResult::forbidden(), $this->accessManager->checkNamedRoute('test_route_1', ['value' => 'example'], $this->account, TRUE));
  }

  /**
   * Tests the checkNamedRoute with default values.
   *
   * @covers ::checkNamedRoute
   */
  public function testCheckNamedRouteWithDefaultValue() {
    $this->routeCollection = new RouteCollection();
    $route = new Route('/test-route-1/{value}', ['value' => 'example'], ['_test_access' => 'TRUE']);
    $this->routeCollection->add('test_route_1', $route);

    $this->routeProvider = $this->createMock('Drupal\Core\Routing\RouteProviderInterface');
    $this->routeProvider->expects($this->any())
      ->method('getRouteByName')
      ->with('test_route_1')
      ->will($this->returnValue($route));

    $map[] = ['test_route_1', ['value' => 'example'], '/test-route-1/example'];

    $this->paramConverter = $this->createMock('Drupal\Core\ParamConverter\ParamConverterManagerInterface');
    $this->paramConverter->expects($this->atLeastOnce())
      ->method('convert')
      ->with(['value' => 'example', RouteObjectInterface::ROUTE_NAME => 'test_route_1', RouteObjectInterface::ROUTE_OBJECT => $route])
      ->will($this->returnValue(['value' => 'upcasted_value']));

    $this->setupAccessArgumentsResolverFactory($this->exactly(2))
      ->with($this->callback(function ($route_match) {
        return $route_match->getParameters()->get('value') == 'upcasted_value';
      }));

    $this->accessManager = new AccessManager($this->routeProvider, $this->paramConverter, $this->argumentsResolverFactory, $this->currentUser, $this->checkProvider);

    $access_check = $this->createMock('Drupal\Tests\Core\Access\TestAccessCheckInterface');
    $access_check->expects($this->atLeastOnce())
      ->method('applies')
      ->will($this->returnValue(TRUE));
    $access_check->expects($this->atLeastOnce())
      ->method('access')
      ->will($this->returnValue(AccessResult::forbidden()));

    $this->container->set('test_access', $access_check);
    $this->container->setParameter('dynamic_access_check_services', ['test_access']);

    $this->checkProvider->addCheckService('test_access', 'access');
    $this->checkProvider->setChecks($this->routeCollection);

    $this->assertEquals(FALSE, $this->accessManager->checkNamedRoute('test_route_1', [], $this->account));
    $this->assertEquals(AccessResult::forbidden(), $this->accessManager->checkNamedRoute('test_route_1', [], $this->account, TRUE));
  }

  /**
   * Tests checkNamedRoute given an invalid/non existing route name.
   */
  public function testCheckNamedRouteWithNonExistingRoute() {
    $this->routeProvider->expects($this->any())
      ->method('getRouteByName')
      ->will($this->throwException(new RouteNotFoundException()));

    $this->setupAccessChecker();

    $this->assertEquals(FALSE, $this->accessManager->checkNamedRoute('test_route_1', [], $this->account), 'A non existing route lead to access.');
    $this->assertEquals(AccessResult::forbidden()->addCacheTags(['config:core.extension']), $this->accessManager->checkNamedRoute('test_route_1', [], $this->account, TRUE), 'A non existing route lead to access.');
  }

  /**
   * Tests that an access checker throws an exception for not allowed values.
   *
   * @dataProvider providerCheckException
   */
  public function testCheckException($return_value) {
    $route_provider = $this->createMock('Drupal\Core\Routing\RouteProviderInterface');

    // Setup a test route for each access configuration.
    $requirements = [
      '_test_incorrect_value' => 'TRUE',
    ];
    $options = [
      '_access_checks' => [
        'test_incorrect_value',
      ],
    ];
    $route = new Route('', [], $requirements, $options);

    $route_provider->expects($this->any())
      ->method('getRouteByName')
      ->will($this->returnValue($route));

    $this->paramConverter = $this->createMock('Drupal\Core\ParamConverter\ParamConverterManagerInterface');
    $this->paramConverter->expects($this->any())
      ->method('convert')
      ->will($this->returnValue([]));

    $this->setupAccessArgumentsResolverFactory();

    $container = new ContainerBuilder();

    // Register a service that will return an incorrect value.
    $access_check = $this->createMock('Drupal\Tests\Core\Access\TestAccessCheckInterface');
    $access_check->expects($this->any())
      ->method('access')
      ->will($this->returnValue($return_value));
    $container->set('test_incorrect_value', $access_check);

    $access_manager = new AccessManager($route_provider, $this->paramConverter, $this->argumentsResolverFactory, $this->currentUser, $this->checkProvider);
    $this->checkProvider->setContainer($container);
    $this->checkProvider->addCheckService('test_incorrect_value', 'access');

    $this->expectException(AccessException::class);
    $access_manager->checkNamedRoute('test_incorrect_value', [], $this->account);
  }

  /**
   * Data provider for testCheckException.
   *
   * @return array
   */
  public function providerCheckException() {
    return [
      [[1]],
      ['string'],
      [0],
      [1],
    ];
  }

  /**
   * Adds a default access check service to the container and the access manager.
   */
  protected function setupAccessChecker() {
    $this->container->register('test_access_default', DefaultAccessCheck::class);
    $this->checkProvider->addCheckService('test_access_default', 'access', ['_access']);
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
      ->willReturnCallback(function ($route_match, $account) {
        $resolver = $this->createMock('Drupal\Component\Utility\ArgumentsResolverInterface');
        $resolver->expects($this->any())
          ->method('getArguments')
          ->will($this->returnCallback(function ($callable) use ($route_match) {
            return [$route_match->getRouteObject()];
          }));

        return $resolver;
      });
  }

}

/**
 * Defines an interface with a defined access() method for mocking.
 */
interface TestAccessCheckInterface extends AccessCheckInterface {

  public function access();

}
