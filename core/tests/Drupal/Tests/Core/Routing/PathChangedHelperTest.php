<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\PathChangedHelper;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\Core\Routing\PathChangedHelper
 * @group Routing
 */
class PathChangedHelperTest extends UnitTestCase {

  /**
   * Tests that the constructor validates its arguments.
   *
   * @covers ::__construct
   */
  public function testPathChangedHelperException(): void {
    $route_match = $this->prophesize(RouteMatchInterface::class);
    $route_match->getRouteName()->willReturn('path.changed.not-bc');

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Drupal\Core\Routing\PathChangedHelper expects a route name that ends with ".bc".');
    new PathChangedHelper($route_match->reveal(), new Request());
  }

}
