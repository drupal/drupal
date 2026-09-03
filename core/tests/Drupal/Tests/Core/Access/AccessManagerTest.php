<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Access;

use Drupal\Component\Utility\ArgumentsResolverInterface;
use Drupal\Core\Access\AccessArgumentsResolverFactoryInterface;
use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessException;
use Drupal\Core\Access\AccessManager;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\CheckProvider;
use Drupal\Core\Access\DefaultAccessCheck;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\ParamConverter\ParamConverterManagerInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\router_test\Access\DefinedTestAccessCheck;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests Drupal\Core\Access\AccessManager.
 */
#[CoversClass(AccessManager::class)]
#[Group('Access')]
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
   * The route provider.
   */
  protected RouteProviderInterface $routeProvider;

  /**
   * The mocked account.
   */
  protected AccountInterface&Stub $account;

  /**
   * The current user.
   */
  protected AccountInterface&Stub $currentUser;

  /**
   * The access arguments resolver factory.
   */
  protected AccessArgumentsResolverFactoryInterface&MockObject $argumentsResolverFactory;

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

    $this->routeProvider = $this->createStub(RouteProviderInterface::class);
    $map = [];
    foreach ($this->routeCollection->all() as $name => $route) {
      $map[] = [$name, $route];
    }
    $map[] = ['test_route_4', $this->routeCollection->get('test_route_4')];
    $this->routeProvider
      ->method('getRouteByName')
      ->willReturnMap($map);

    $this->account = $this->createStub(AccountInterface::class);
    $this->currentUser = $this->createStub(AccountInterface::class);
    $this->checkProvider = new CheckProvider([], $this->container);
  }

  /**
   * Tests \Drupal\Core\Access\AccessManager::setChecks().
   */
  public function testSetChecks(): void {
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
  public function testSetChecksWithDynamicAccessChecker(): void {
    // Setup the dynamic access checker.
    $access_check = $this->createMock('Drupal\Tests\Core\Access\TestAccessCheckInterface');
    $this->container->set('test_access', $access_check);
    $this->checkProvider = new CheckProvider(['test_access'], $this->container);
    $this->checkProvider->addCheckService('test_access', 'access');

    $route = new Route('/test-path', [], ['_foo' => '1', '_bar' => '1']);
    $route2 = new Route('/test-path', [], ['_foo' => '1', '_bar' => '2']);
    $collection = new RouteCollection();
    $collection->add('test_route', $route);
    $collection->add('test_route2', $route2);

    $access_check->expects($this->exactly(2))
      ->method('applies')
      ->with($this->isInstanceOf('Symfony\Component\Routing\Route'))
      ->willReturnCallback(function (Route $route): bool {
        return $route->getRequirement('_bar') == 2;
      });

    $this->checkProvider->setChecks($collection);
    $this->assertEmpty($route->getOption('_access_checks'));
    $this->assertEquals(['test_access'], $route2->getOption('_access_checks'));
  }

  /**
   * Tests \Drupal\Core\Access\AccessManager::check().
   */
  public function testCheck(): void {
    $route_matches = [];
    $this->argumentsResolverFactory = $this->createMock(AccessArgumentsResolverFactoryInterface::class);
    $accessManager = new AccessManager(
      $this->routeProvider,
      $this->createStub(ParamConverterManagerInterface::class),
      $this->argumentsResolverFactory,
      $this->currentUser,
      $this->checkProvider,
    );

    // Construct route match objects.
    foreach ($this->routeCollection->all() as $route_name => $route) {
      $route_matches[$route_name] = new RouteMatch($route_name, $route, [], []);
    }

    // Check route access without any access checker defined yet.
    foreach ($route_matches as $route_match) {
      $this->assertEquals(FALSE, $accessManager->check($route_match, $this->account));
      $this->assertEquals(AccessResult::neutral(), $accessManager->check($route_match, $this->account, NULL, TRUE));
    }

    $this->setupAccessChecker();

    // An access checker got setup, but the routes haven't been setup using
    // setChecks.
    foreach ($route_matches as $route_match) {
      $this->assertEquals(FALSE, $accessManager->check($route_match, $this->account));
      $this->assertEquals(AccessResult::neutral(), $accessManager->check($route_match, $this->account, NULL, TRUE));
    }

    // Now applicable access checks have been saved on each route object.
    $this->checkProvider->setChecks($this->routeCollection);
    $this->setupAccessArgumentsResolverFactory();

    $this->assertEquals(FALSE, $accessManager->check($route_matches['test_route_1'], $this->account));
    $this->assertEquals(TRUE, $accessManager->check($route_matches['test_route_2'], $this->account));
    $this->assertEquals(FALSE, $accessManager->check($route_matches['test_route_3'], $this->account));
    $this->assertEquals(TRUE, $accessManager->check($route_matches['test_route_4'], $this->account));
    $this->assertEquals(AccessResult::neutral(), $accessManager->check($route_matches['test_route_1'], $this->account, NULL, TRUE));
    $this->assertEquals(AccessResult::allowed(), $accessManager->check($route_matches['test_route_2'], $this->account, NULL, TRUE));
    $this->assertEquals(AccessResult::forbidden(), $accessManager->check($route_matches['test_route_3'], $this->account, NULL, TRUE));
    $this->assertEquals(AccessResult::allowed(), $accessManager->check($route_matches['test_route_4'], $this->account, NULL, TRUE));
  }

  /**
   * Tests \Drupal\Core\Access\AccessManager::check() with no account specified.
   */
  public function testCheckWithNullAccount(): void {
    $this->setupAccessChecker();
    $this->checkProvider->setChecks($this->routeCollection);

    $route = $this->routeCollection->get('test_route_2');
    $route_match = new RouteMatch('test_route_2', $route, [], []);

    $this->argumentsResolverFactory = $this->createMock(AccessArgumentsResolverFactoryInterface::class);
    // Asserts that the current user is passed to the access arguments resolver
    // factory.
    $this->setupAccessArgumentsResolverFactory(NULL, [$route_match, $this->currentUser, NULL]);

    $accessManager = new AccessManager(
      $this->routeProvider,
      $this->createStub(ParamConverterManagerInterface::class),
      $this->argumentsResolverFactory,
      $this->currentUser,
      $this->checkProvider,
    );

    $this->assertTrue($accessManager->check($route_match));
  }

  /**
   * Provides data for the conjunction test.
   *
   * @return array
   *   An array of data for check conjunctions.
   *
   * @see \Drupal\Tests\Core\Access\AccessManagerTest::testCheckConjunctions()
   */
  public static function providerTestCheckConjunctions(): array {
    $access_allow = AccessResult::allowed();
    $access_deny = AccessResult::neutral();
    $access_kill = AccessResult::forbidden();

    $access_configurations = [];
    $access_configurations[] = [
      'name' => 'test_route_4',
      'condition_one' => 'TRUE',
      'condition_two' => 'FALSE',
      'expected_access' => $access_kill,
    ];
    $access_configurations[] = [
      'name' => 'test_route_5',
      'condition_one' => 'TRUE',
      'condition_two' => 'NULL',
      'expected_access' => $access_deny,
    ];
    $access_configurations[] = [
      'name' => 'test_route_6',
      'condition_one' => 'FALSE',
      'condition_two' => 'NULL',
      'expected_access' => $access_kill,
    ];
    $access_configurations[] = [
      'name' => 'test_route_7',
      'condition_one' => 'TRUE',
      'condition_two' => 'TRUE',
      'expected_access' => $access_allow,
    ];
    $access_configurations[] = [
      'name' => 'test_route_8',
      'condition_one' => 'FALSE',
      'condition_two' => 'FALSE',
      'expected_access' => $access_kill,
    ];
    $access_configurations[] = [
      'name' => 'test_route_9',
      'condition_one' => 'NULL',
      'condition_two' => 'NULL',
      'expected_access' => $access_deny,
    ];

    return $access_configurations;
  }

  /**
   * Tests \Drupal\Core\Access\AccessManager::check() with conjunctions.
   */
  #[DataProvider('providerTestCheckConjunctions')]
  public function testCheckConjunctions(string $name, $condition_one, $condition_two, $expected_access): void {
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
    $this->argumentsResolverFactory = $this->createMock(AccessArgumentsResolverFactoryInterface::class);
    $this->setupAccessArgumentsResolverFactory();

    $accessManager = new AccessManager(
      $this->routeProvider,
      $this->createStub(ParamConverterManagerInterface::class),
      $this->argumentsResolverFactory,
      $this->currentUser,
      $this->checkProvider,
    );

    $route_match = new RouteMatch($name, $route, [], []);
    $this->assertEquals($expected_access->isAllowed(), $accessManager->check($route_match, $this->account));
    $this->assertEquals($expected_access, $accessManager->check($route_match, $this->account, NULL, TRUE));
  }

  /**
   * Tests the checkNamedRoute method.
   *
   * @see \Drupal\Core\Access\AccessManager::checkNamedRoute()
   */
  public function testCheckNamedRoute(): void {
    $paramConverter = $this->createMock(ParamConverterManagerInterface::class);
    $this->argumentsResolverFactory = $this->createMock(AccessArgumentsResolverFactoryInterface::class);
    $this->setupAccessChecker();
    $accessManager = new AccessManager(
      $this->routeProvider,
      $paramConverter,
      $this->argumentsResolverFactory,
      $this->currentUser,
      $this->checkProvider,
    );

    $this->checkProvider->setChecks($this->routeCollection);
    $this->setupAccessArgumentsResolverFactory();

    $paramConverter->expects($this->exactly(4))
      ->method('convert')
      ->willReturnMap([
        [
          [
            RouteObjectInterface::ROUTE_NAME => 'test_route_2',
            RouteObjectInterface::ROUTE_OBJECT => $this->routeCollection->get('test_route_2'),
          ],
          [],
        ],
        [
          [
            'value' => 'example',
            RouteObjectInterface::ROUTE_NAME => 'test_route_4',
            RouteObjectInterface::ROUTE_OBJECT => $this->routeCollection->get('test_route_4'),
          ],
          ['value' => 'example'],
        ],
      ]);

    // Tests the access with routes with parameters without given request.
    $this->assertEquals(TRUE, $accessManager->checkNamedRoute('test_route_2', [], $this->account));
    $this->assertEquals(AccessResult::allowed(), $accessManager->checkNamedRoute('test_route_2', [], $this->account, TRUE));
    $this->assertEquals(TRUE, $accessManager->checkNamedRoute('test_route_4', ['value' => 'example'], $this->account));
    $this->assertEquals(AccessResult::allowed(), $accessManager->checkNamedRoute('test_route_4', ['value' => 'example'], $this->account, TRUE));
  }

  /**
   * Tests the checkNamedRoute with upcasted values.
   *
   * @see \Drupal\Core\Access\AccessManager::checkNamedRoute()
   */
  public function testCheckNamedRouteWithUpcastedValues(): void {
    $this->routeCollection = new RouteCollection();
    $route = new Route('/test-route-1/{value}', [], ['_test_access' => 'TRUE']);
    $this->routeCollection->add('test_route_1', $route);

    $routeProvider = $this->createMock(RouteProviderInterface::class);
    $routeProvider->expects($this->atLeastOnce())
      ->method('getRouteByName')
      ->with('test_route_1')
      ->willReturn($route);

    $map[] = ['test_route_1', ['value' => 'example'], '/test-route-1/example'];

    $paramConverter = $this->createMock(ParamConverterManagerInterface::class);
    $paramConverter->expects($this->atLeastOnce())
      ->method('convert')
      ->with([
        'value' => 'example',
        RouteObjectInterface::ROUTE_NAME => 'test_route_1',
        RouteObjectInterface::ROUTE_OBJECT => $route,
      ])
      ->willReturn(['value' => 'upcasted_value']);

    $this->argumentsResolverFactory = $this->createMock(AccessArgumentsResolverFactoryInterface::class);
    $this->setupAccessArgumentsResolverFactory($this->exactly(2), $this->callback(function ($route_match): bool {
      return $route_match->getParameters()->get('value') == 'upcasted_value';
    }));

    $access_check = $this->createMock('Drupal\Tests\Core\Access\TestAccessCheckInterface');
    $access_check->expects($this->atLeastOnce())
      ->method('applies')
      ->willReturn(TRUE);
    $access_check->expects($this->atLeastOnce())
      ->method('access')
      ->willReturn(AccessResult::forbidden());

    $this->container->set('test_access', $access_check);
    $this->checkProvider = new CheckProvider(['test_access'], $this->container);
    $this->checkProvider->addCheckService('test_access', 'access');
    $this->checkProvider->setChecks($this->routeCollection);

    $accessManager = new AccessManager(
      $routeProvider,
      $paramConverter,
      $this->argumentsResolverFactory,
      $this->currentUser,
      $this->checkProvider,
    );

    $this->assertEquals(FALSE, $accessManager->checkNamedRoute('test_route_1', ['value' => 'example'], $this->account));
    $this->assertEquals(AccessResult::forbidden(), $accessManager->checkNamedRoute('test_route_1', ['value' => 'example'], $this->account, TRUE));
  }

  /**
   * Tests the checkNamedRoute with default values.
   */
  public function testCheckNamedRouteWithDefaultValue(): void {
    $this->routeCollection = new RouteCollection();
    $route = new Route('/test-route-1/{value}', ['value' => 'example'], ['_test_access' => 'TRUE']);
    $this->routeCollection->add('test_route_1', $route);

    $routeProvider = $this->createMock(RouteProviderInterface::class);
    $routeProvider->expects($this->atLeastOnce())
      ->method('getRouteByName')
      ->with('test_route_1')
      ->willReturn($route);

    $map[] = ['test_route_1', ['value' => 'example'], '/test-route-1/example'];

    $paramConverter = $this->createMock(ParamConverterManagerInterface::class);
    $paramConverter->expects($this->atLeastOnce())
      ->method('convert')
      ->with([
        'value' => 'example',
        RouteObjectInterface::ROUTE_NAME => 'test_route_1',
        RouteObjectInterface::ROUTE_OBJECT => $route,
      ])
      ->willReturn(['value' => 'upcasted_value']);

    $this->argumentsResolverFactory = $this->createMock(AccessArgumentsResolverFactoryInterface::class);
    $this->setupAccessArgumentsResolverFactory($this->exactly(2), $this->callback(function ($route_match): bool {
      return $route_match->getParameters()->get('value') == 'upcasted_value';
    }));

    $access_check = $this->createMock('Drupal\Tests\Core\Access\TestAccessCheckInterface');
    $access_check->expects($this->atLeastOnce())
      ->method('applies')
      ->willReturn(TRUE);
    $access_check->expects($this->atLeastOnce())
      ->method('access')
      ->willReturn(AccessResult::forbidden());

    $this->container->set('test_access', $access_check);
    $this->checkProvider = new CheckProvider(['test_access'], $this->container);
    $this->checkProvider->addCheckService('test_access', 'access');
    $this->checkProvider->setChecks($this->routeCollection);

    $accessManager = new AccessManager(
      $routeProvider,
      $paramConverter,
      $this->argumentsResolverFactory,
      $this->currentUser,
      $this->checkProvider,
    );

    $this->assertEquals(FALSE, $accessManager->checkNamedRoute('test_route_1', [], $this->account));
    $this->assertEquals(AccessResult::forbidden(), $accessManager->checkNamedRoute('test_route_1', [], $this->account, TRUE));
  }

  /**
   * Tests checkNamedRoute given an invalid/non existing route name.
   */
  public function testCheckNamedRouteWithNonExistingRoute(): void {
    $this->routeProvider
      ->method('getRouteByName')
      ->will($this->throwException(new RouteNotFoundException()));
    $accessManager = new AccessManager(
      $this->routeProvider,
      $this->createStub(ParamConverterManagerInterface::class),
      $this->createStub(AccessArgumentsResolverFactoryInterface::class),
      $this->currentUser,
      $this->checkProvider,
    );

    $this->setupAccessChecker();

    $this->assertEquals(FALSE, $accessManager->checkNamedRoute('test_route_1', [], $this->account), 'A non existing route lead to access.');
    $this->assertEquals(AccessResult::forbidden()->addCacheTags(['config:core.extension']), $accessManager->checkNamedRoute('test_route_1', [], $this->account, TRUE), 'A non existing route lead to access.');
  }

  /**
   * Tests that an access checker throws an exception for not allowed values.
   */
  #[DataProvider('providerCheckException')]
  public function testCheckException(string|int|array $return_value): void {
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

    $routeProvider = $this->createStub(RouteProviderInterface::class);
    $routeProvider
      ->method('getRouteByName')
      ->willReturn($route);

    $paramConverter = $this->createMock(ParamConverterManagerInterface::class);
    $paramConverter->expects($this->atLeastOnce())
      ->method('convert')
      ->willReturn([]);

    $this->argumentsResolverFactory = $this->createMock(AccessArgumentsResolverFactoryInterface::class);
    $this->setupAccessArgumentsResolverFactory();

    $container = new ContainerBuilder();

    // Register a service that will return an incorrect value.
    $access_check = $this->createStub(TestAccessCheckInterface::class);
    $access_check
      ->method('access')
      ->willReturn($return_value);
    $container->set('test_incorrect_value', $access_check);

    $this->checkProvider = new CheckProvider([], $container);
    $this->checkProvider->addCheckService('test_incorrect_value', 'access');
    $access_manager = new AccessManager(
      $routeProvider,
      $paramConverter,
      $this->argumentsResolverFactory,
      $this->currentUser,
      $this->checkProvider,
    );

    $this->expectException(AccessException::class);
    $access_manager->checkNamedRoute('test_incorrect_value', [], $this->account);
  }

  /**
   * Data provider for testCheckException.
   *
   * @return array
   *   An array of data for check exceptions.
   */
  public static function providerCheckException(): array {
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
  protected function setupAccessChecker(): void {
    $this->container->register('test_access_default', DefaultAccessCheck::class);
    $this->checkProvider->addCheckService('test_access_default', 'access', ['_access']);
  }

  /**
   * Add default expectations to the access arguments resolver factory.
   *
   * @param \PHPUnit\Framework\MockObject\Rule\InvocationOrder|null $constraint
   *   An expectation constraint to apply to the factory.
   * @param array|\PHPUnit\Framework\Constraint\Callback|null $withParameters
   *   Parameters for the ::with() method, or NULL if not required.
   */
  protected function setupAccessArgumentsResolverFactory(?InvocationOrder $constraint = NULL, array|Callback|null $withParameters = NULL): void {
    if (!isset($constraint)) {
      $constraint = $this->atLeastOnce();
    }
    $mock = $this->argumentsResolverFactory
      ->expects($constraint)
      ->method('getArgumentsResolver')
      ->willReturnCallback(function ($route_match, $account): Stub {
        $resolver = $this->createStub(ArgumentsResolverInterface::class);
        $resolver
          ->method('getArguments')
          ->willReturnCallback(function ($callable) use ($route_match): array {
            return [$route_match->getRouteObject()];
          });

        return $resolver;
      });
    if ($withParameters) {
      if (is_array($withParameters)) {
        $mock->with(...$withParameters);
      }
      else {
        $mock->with($withParameters);
      }
    }
  }

}

/**
 * Defines an interface with a defined access() method for mocking.
 */
interface TestAccessCheckInterface extends AccessCheckInterface {

  public function access(): int|string|array|AccessResultForbidden;

}
