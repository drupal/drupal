<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\RouteProcessor\RouteProcessorManagerTest.
 */

namespace Drupal\Tests\Core\RouteProcessor;

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

  public function setUp() {
    $this->processorManager = new RouteProcessorManager();
  }

  /**
   * Tests the Route process manager functionality.
   */
  public function testRouteProcessorManager() {
    $route = new Route('');
    $parameters = array('test' => 'test');

    $processors = array(
      10 => $this->getMockProcessor($route, $parameters),
      5 => $this->getMockProcessor($route, $parameters),
      0 => $this->getMockProcessor($route, $parameters),
    );

    // Add the processors in reverse order.
    foreach ($processors as $priority => $processor) {
      $this->processorManager->addOutbound($processor, $priority);
    }

    $this->processorManager->processOutbound($route, $parameters);
  }

  /**
   * Returns a mock Route processor object.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The Route to use in mock with() expectation.
   * @param array $parameters
   *   The parameters to use in mock with() expectation.
   *
   * @return \Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected function getMockProcessor($route, $parameters) {
    $processor = $this->getMock('Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface');
    $processor->expects($this->once())
      ->method('processOutbound')
      ->with($route, $parameters);

    return $processor;
  }

}
