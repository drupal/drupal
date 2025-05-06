<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\PageCache;

use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Core\PageCache\ResponsePolicy\DenyNoCacheRoutes;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\PageCache\ResponsePolicy\DenyNoCacheRoutes
 * @group PageCache
 * @group Route
 */
class DenyNoCacheRoutesTest extends UnitTestCase {

  /**
   * The response policy under test.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\DenyNoCacheRoutes
   */
  protected $policy;

  /**
   * A request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * A response object.
   *
   * @var \Symfony\Component\HttpFoundation\Response
   */
  protected $response;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatch|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->policy = new DenyNoCacheRoutes($this->routeMatch);
    $this->response = new Response();
    $this->request = new Request();
  }

  /**
   * Asserts that caching is denied on the node preview route.
   *
   * @dataProvider providerDenyNoCacheRoutesPolicy
   * @covers ::check
   */
  public function testDenyNoCacheRoutesPolicy($expected_result, ?Route $route): void {
    $this->routeMatch->expects($this->once())
      ->method('getRouteObject')
      ->willReturn($route);

    $actual_result = $this->policy->check($this->response, $this->request);
    $this->assertSame($expected_result, $actual_result);
  }

  /**
   * Provides data and expected results for the test method.
   *
   * @return array
   *   Data and expected results.
   */
  public static function providerDenyNoCacheRoutesPolicy(): array {
    $no_cache_route = new Route('', [], [], ['no_cache' => TRUE]);
    return [
      [ResponsePolicyInterface::DENY, $no_cache_route],
      [NULL, new Route('')],
      [NULL, NULL],
    ];
  }

}
