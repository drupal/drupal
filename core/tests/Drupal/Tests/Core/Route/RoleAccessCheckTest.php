<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Route\RoleAccessCheckTest.
 */

namespace Drupal\Tests\Core\Route;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Access\RoleAccessCheck;
use Drupal\user\Plugin\Core\Entity\User;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines tests for role based access in routes.
 *
 * @see \Drupal\user\Access\RoleAccessCheck
 */
class RoleAccessCheckTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Router Role tests',
      'description' => 'Test for the role based access checker in the routing system.',
      'group' => 'Routing',
    );
  }

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
    $account_1 = new User(array('uid' => 1), 'user');
    $account_1->roles[$rid_1] = $rid_1;

    $account_2 = new User(array('uid' => 2), 'user');
    $account_2->roles[$rid_2] = $rid_2;

    $account_12 = new User(array('uid' => 3), 'user');
    $account_12->roles[$rid_1] = $rid_1;
    $account_12->roles[$rid_2] = $rid_2;

    $account_none = new User(array('uid' => 4), 'user');

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
      // @todo Replace the global user with a properly injection session.
      $GLOBALS['user'] = $account;

      $subrequest = Request::create($path, 'GET');
      $message = sprintf('Access granted for user with the roles %s on path: %s', implode(', ', $account->roles), $path);
      $this->assertTrue($role_access_check->access($collection->get($path), $subrequest), $message);
    }

    // Check all users which don't have access.
    foreach ($deny_accounts as $account) {
      $GLOBALS['user'] = $account;

      $subrequest = Request::create($path, 'GET');
      $message = sprintf('Access denied for user %s with the roles %s on path: %s', $account->id(), implode(', ', $account->roles), $path);
      $has_access = $role_access_check->access($collection->get($path), $subrequest);
      $this->assertEmpty($has_access , $message);
    }
  }

}
