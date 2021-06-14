<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Authentication\AuthenticationManagerTest.
 */

namespace Drupal\Tests\Core\Authentication;

use Drupal\Core\Authentication\AuthenticationCollector;
use Drupal\Core\Authentication\AuthenticationManager;
use Drupal\Core\Authentication\AuthenticationProviderFilterInterface;
use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Authentication\AuthenticationManager
 * @group Authentication
 */
class AuthenticationManagerTest extends UnitTestCase {

  /**
   * @covers ::defaultFilter
   * @covers ::applyFilter
   *
   * @dataProvider providerTestDefaultFilter
   */
  public function testDefaultFilter($applies, $has_route, $auth_option, $provider_id, $global) {
    $auth_provider = $this->createMock('Drupal\Core\Authentication\AuthenticationProviderInterface');
    $auth_collector = new AuthenticationCollector();
    $auth_collector->addProvider($auth_provider, $provider_id, 0, $global);
    $authentication_manager = new AuthenticationManager($auth_collector);

    $request = new Request();
    if ($has_route) {
      $route = new Route('/example');
      if ($auth_option) {
        $route->setOption('_auth', $auth_option);
      }
      $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, $route);
    }

    $this->assertSame($applies, $authentication_manager->appliesToRoutedRequest($request, FALSE));
  }

  /**
   * @covers ::applyFilter
   */
  public function testApplyFilterWithFilterprovider() {
    $auth_provider = $this->createMock('Drupal\Tests\Core\Authentication\TestAuthenticationProviderInterface');
    $auth_provider->expects($this->once())
      ->method('appliesToRoutedRequest')
      ->willReturn(TRUE);

    $authentication_collector = new AuthenticationCollector();
    $authentication_collector->addProvider($auth_provider, 'filtered', 0);

    $authentication_manager = new AuthenticationManager($authentication_collector);

    $request = new Request();
    $this->assertTrue($authentication_manager->appliesToRoutedRequest($request, FALSE));
  }

  /**
   * Provides data to self::testDefaultFilter().
   */
  public function providerTestDefaultFilter() {
    $data = [];
    // No route, cookie is global, should apply.
    $data[] = [TRUE, FALSE, [], 'cookie', TRUE];
    // No route, cookie is not global, should not apply.
    $data[] = [FALSE, FALSE, [], 'cookie', FALSE];
    // Route, no _auth, cookie is global, should apply.
    $data[] = [TRUE, TRUE, [], 'cookie', TRUE];
    // Route, no _auth, cookie is not global, should not apply.
    $data[] = [FALSE, TRUE, [], 'cookie', FALSE];
    // Route, with _auth and non-matching provider, should not apply.
    $data[] = [FALSE, TRUE, ['basic_auth'], 'cookie', TRUE];
    // Route, with _auth and matching provider should not apply.
    $data[] = [TRUE, TRUE, ['basic_auth'], 'basic_auth', TRUE];
    return $data;
  }

}

/**
 * Helper interface to mock two interfaces at once.
 */
interface TestAuthenticationProviderInterface extends AuthenticationProviderFilterInterface, AuthenticationProviderInterface {}
