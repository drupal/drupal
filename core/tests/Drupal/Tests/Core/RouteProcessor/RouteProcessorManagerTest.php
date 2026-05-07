<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\RouteProcessor;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\RouteProcessor\RouteProcessorManager;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Routing\Route;

/**
 * Tests Drupal\Core\RouteProcessor\RouteProcessorManager.
 */
#[CoversClass(RouteProcessorManager::class)]
#[Group('RouteProcessor')]
class RouteProcessorManagerTest extends UnitTestCase {

  /**
   * Tests the Route process manager functionality.
   */
  public function testRouteProcessorManager(): void {
    $route = new Route('');
    $parameters = ['test' => 'test'];
    $route_name = 'test_name';

    $processorManager = new RouteProcessorManager([
      $this->getMockProcessor($route_name, $route, $parameters),
      $this->getMockProcessor($route_name, $route, $parameters),
      $this->getMockProcessor($route_name, $route, $parameters),
    ]);

    $bubbleable_metadata = new BubbleableMetadata();
    $processorManager->processOutbound($route_name, $route, $parameters, $bubbleable_metadata);
    // Default cacheability is: permanently cacheable, no cache tags/contexts.
    $this->assertEquals((new BubbleableMetadata())->setCacheMaxAge(Cache::PERMANENT), $bubbleable_metadata);
  }

  /**
   * Returns a mock Route processor object.
   *
   * @param string $route_name
   *   The route name.
   * @param \Symfony\Component\Routing\Route $route
   *   The Route to use in mock with() expectation.
   * @param array $parameters
   *   The parameters to use in mock with() expectation.
   *
   * @return \Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The mock processor object.
   */
  protected function getMockProcessor($route_name, $route, $parameters) {
    $processor = $this->createMock('Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface');
    $processor->expects($this->once())
      ->method('processOutbound')
      ->with($route_name, $route, $parameters);

    return $processor;
  }

}
