<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Access;

use Drupal\Core\Access\CsrfRequestHeaderAccessCheck;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Tests that the CSRF request header access check is attached to routes.
 */
#[CoversClass(CsrfRequestHeaderAccessCheck::class)]
#[Group('Access')]
#[RunTestsInSeparateProcesses]
class CsrfRequestHeaderAccessCheckTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['csrf_test'];

  /**
   * Tests which routes the access check is attached to.
   *
   * The check is registered against the '_csrf_request_header_token'
   * requirement, so it is attached regardless of the methods a route allows.
   * There is no need to filter out read-only routes when building the route
   * collection, because CsrfRequestHeaderAccessCheck::access() allows
   * read-only requests.
   *
   * @param string $route_name
   *   The name of the route to check.
   * @param bool $expected
   *   Whether the access check is expected to be attached to the route.
   */
  #[TestWith(['csrf_test.protected', TRUE], 'write method')]
  #[TestWith(['csrf_test.protected_read_only', TRUE], 'read-only method')]
  #[TestWith(['csrf_test.route_with_csrf_token', FALSE], 'no requirement')]
  public function testChecksAttachedToRoutes(string $route_name, bool $expected): void {
    $route = $this->container->get('router.route_provider')->getRouteByName($route_name);
    $access_checks = $route->getOption('_access_checks') ?? [];
    if ($expected) {
      $this->assertContains('access_check.header.csrf', $access_checks);
    }
    else {
      $this->assertNotContains('access_check.header.csrf', $access_checks);
    }
  }

}
