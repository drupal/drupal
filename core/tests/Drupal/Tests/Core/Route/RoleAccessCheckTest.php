<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Route\RoleAccessCheckTest.
 */

namespace Drupal\Tests\Core\Route;

use Drupal\Core\Access\AccessResult;
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
      array(
        '_controller' => '\Drupal\router_test\TestControllers::test1',
      ),
      array(
        '_role' => 'role_test_1',
      )
    ));
    $route_collection->add('role_test_2', new Route('/role_test_2',
      array(
        '_controller' => '\Drupal\router_test\TestControllers::test1',
      ),
      array(
        '_role' => 'role_test_2',
      )
    ));
    $route_collection->add('role_test_3', new Route('/role_test_3',
      array(
        '_controller' => '\Drupal\router_test\TestControllers::test1',
      ),
      array(
        '_role' => 'role_test_1+role_test_2',
      )
    ));
    // Ensure that trimming the values works on "OR" conjunctions.
    $route_collection->add('role_test_4', new Route('/role_test_4',
      array(
        '_controller' => '\Drupal\router_test\TestControllers::test1',
      ),
      array(
        '_role' => 'role_test_1 + role_test_2',
      )
    ));
    $route_collection->add('role_test_5', new Route('/role_test_5',
      array(
        '_controller' => '\Drupal\router_test\TestControllers::test1',
      ),
      array(
        '_role' => 'role_test_1,role_test_2',
      )
    ));
    // Ensure that trimming the values works on "AND" conjunctions.
    $route_collection->add('role_test_6', new Route('/role_test_6',
      array(
        '_controller' => '\Drupal\router_test\TestControllers::test1',
      ),
      array(
        '_role' => 'role_test_1 , role_test_2',
      )
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

    $account_1 = new UserSession(array(
      'uid' => 1,
      'roles' => array($rid_1),
    ));

    $account_2 = new UserSession(array(
      'uid' => 2,
      'roles' => array($rid_2),
    ));

    $account_12 = new UserSession(array(
      'uid' => 3,
      'roles' => array($rid_1, $rid_2),
    ));

    $account_none = new UserSession(array(
      'uid' => 1,
      'roles' => array(),
    ));

    // Setup expected values; specify which paths can be accessed by which user.
    return array(
      array('role_test_1', array($account_1, $account_12), array($account_2, $account_none)),
      array('role_test_2', array($account_2, $account_12), array($account_1, $account_none)),
      array('role_test_3', array($account_12), array($account_1, $account_2, $account_none)),
      array('role_test_4', array($account_12), array($account_1, $account_2, $account_none)),
      array('role_test_5', array($account_1, $account_2, $account_12), array()),
      array('role_test_6', array($account_1, $account_2, $account_12), array()),
    );
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
    $role_access_check = new RoleAccessCheck();
    $collection = $this->getTestRouteCollection();

    foreach ($grant_accounts as $account) {
      $message = sprintf('Access granted for user with the roles %s on path: %s', implode(', ', $account->getRoles()), $path);
      $this->assertEquals(AccessResult::allowed()->cachePerRole(), $role_access_check->access($collection->get($path), $account), $message);
    }

    // Check all users which don't have access.
    foreach ($deny_accounts as $account) {
      $message = sprintf('Access denied for user %s with the roles %s on path: %s', $account->id(), implode(', ', $account->getRoles()), $path);
      $has_access = $role_access_check->access($collection->get($path), $account);
      $this->assertEquals(AccessResult::create()->cachePerRole(), $has_access, $message);
    }
  }

}
