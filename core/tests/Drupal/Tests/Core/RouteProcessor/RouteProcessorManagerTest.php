<?php

namespace Drupal\Tests\Core\RouteProcessor;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\RouteProcessor\RouteProcessorManager;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\RouteProcessor\RouteProcessorManager
 * @group RouteProcessor
 */
class RouteProcessorManagerTest extends UnitTestCase {

  /**
   * The route processor manager.
   *
   * @var \Drupal\Core\RouteProcessor\RouteProcessorManager
   */
  protected $processorManager;

  protected function setUp() {
    $this->processorManager = new RouteProcessorManager();
  }

  /**
   * Tests the Route process manager functionality.
   */
  public function testRouteProcessorManager() {
    $route = new Route('');
    $parameters = ['test' => 'test'];
    $route_name = 'test_name';

    $processors = [
      10 => $this->getMockProcessor($route_name, $route, $parameters),
      5 => $this->getMockProcessor($route_name, $route, $parameters),
      0 => $this->getMockProcessor($route_name, $route, $parameters),
    ];

    // Add the processors in reverse order.
    foreach ($processors as $priority => $processor) {
      $this->processorManager->addOutbound($processor, $priority);
    }

    $bubbleable_metadata = new BubbleableMetadata();
    $this->processorManager->processOutbound($route_name, $route, $parameters, $bubbleable_metadata);
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
   */
  protected function getMockProcessor($route_name, $route, $parameters) {
    $processor = $this->createMock('Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface');
    $processor->expects($this->once())
      ->method('processOutbound')
      ->with($route_name, $route, $parameters);

    return $processor;
  }

}
