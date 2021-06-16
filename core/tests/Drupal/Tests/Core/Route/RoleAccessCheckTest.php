<?php

namespace Drupal\Tests\Core\Route;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Session\UserSession;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Access\RoleAccessCheck;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\user\Access\RoleAccessCheck
 * @group Access
 * @group Route
 */
class RoleAccessCheckTest extends UnitTestCase {

  /**
   * Generates the test route collection.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   Returns the test route collection.
   */
  protected function getTestRouteCollection() {
    $route_collection = new RouteCollection();
    $route_collection->add('role_test_1', new Route('/role_test_1',
      [
        '_controller' => '\Drupal\router_test\TestControllers::test1',
      ],
      [
        '_role' => 'role_test_1',
      ]
    ));
    $route_collection->add('role_test_2', new Route('/role_test_2',
      [
        '_controller' => '\Drupal\router_test\TestControllers::test1',
      ],
      [
        '_role' => 'role_test_2',
      ]
    ));
    $route_collection->add('role_test_3', new Route('/role_test_3',
      [
        '_controller' => '\Drupal\router_test\TestControllers::test1',
      ],
      [
        '_role' => 'role_test_1,role_test_2',
      ]
    ));
    // Ensure that trimming the values works on "OR" conjunctions.
    $route_collection->add('role_test_4', new Route('/role_test_4',
      [
        '_controller' => '\Drupal\router_test\TestControllers::test1',
      ],
      [
        '_role' => 'role_test_1 , role_test_2',
      ]
    ));
    $route_collection->add('role_test_5', new Route('/role_test_5',
      [
        '_controller' => '\Drupal\router_test\TestControllers::test1',
      ],
      [
        '_role' => 'role_test_1+role_test_2',
      ]
    ));
    // Ensure that trimming the values works on "AND" conjunctions.
    $route_collection->add('role_test_6', new Route('/role_test_6',
      [
        '_controller' => '\Drupal\router_test\TestControllers::test1',
      ],
      [
        '_role' => 'role_test_1 + role_test_2',
      ]
    ));

    return $route_collection;
  }

  /**
   * Provides data for the role access test.
   *
   * @see \Drupal\Tests\Core\Route\RouterRoleTest::testRoleAccess
   */
  public function roleAccessProvider() {
    // Setup two different roles used in the test.
    $rid_1 = 'role_test_1';
    $rid_2 = 'role_test_2';

    // Setup one user with the first role, one with the second, one with both
    // and one final without any of these two roles.

    $account_1 = new UserSession([
      'uid' => 1,
      'roles' => [$rid_1],
    ]);

    $account_2 = new UserSession([
      'uid' => 2,
      'roles' => [$rid_2],
    ]);

    $account_12 = new UserSession([
      'uid' => 3,
      'roles' => [$rid_1, $rid_2],
    ]);

    $account_none = new UserSession([
      'uid' => 1,
      'roles' => [],
    ]);

    // Setup expected values; specify which paths can be accessed by which user.
    return [
      ['role_test_1', [$account_1, $account_12], [$account_2, $account_none]],
      ['role_test_2', [$account_2, $account_12], [$account_1, $account_none]],
      ['role_test_3', [$account_12], [$account_1, $account_2, $account_none]],
      ['role_test_4', [$account_12], [$account_1, $account_2, $account_none]],
      ['role_test_5', [$account_1, $account_2, $account_12], []],
      ['role_test_6', [$account_1, $account_2, $account_12], []],
    ];
  }

  /**
   * Tests role requirements on routes.
   *
   * @param string $path
   *   The path to check access for.
   * @param array $grant_accounts
   *   A list of accounts which should have access to the given path.
   * @param array $deny_accounts
   *   A list of accounts which should not have access to the given path.
   *
   * @see \Drupal\Tests\Core\Route\RouterRoleTest::getTestRouteCollection
   * @see \Drupal\Tests\Core\Route\RouterRoleTest::roleAccessProvider
   *
   * @dataProvider roleAccessProvider
   */
  public function testRoleAccess($path, $grant_accounts, $deny_accounts) {
    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens()->willReturn(TRUE);
    $cache_contexts_manager->reveal();
    $container = new Container();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);

    $role_access_check = new RoleAccessCheck();
    $collection = $this->getTestRouteCollection();

    foreach ($grant_accounts as $account) {
      $message = sprintf('Access granted for user with the roles %s on path: %s', implode(', ', $account->getRoles()), $path);
      $this->assertEquals(AccessResult::allowed()->addCacheContexts(['user.roles']), $role_access_check->access($collection->get($path), $account), $message);
    }

    // Check all users which don't have access.
    foreach ($deny_accounts as $account) {
      $message = sprintf('Access denied for user %s with the roles %s on path: %s', $account->id(), implode(', ', $account->getRoles()), $path);
      $has_access = $role_access_check->access($collection->get($path), $account);
      $this->assertEquals(AccessResult::neutral()->addCacheContexts(['user.roles']), $has_access, $message);
    }
  }

}
