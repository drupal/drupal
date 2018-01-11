<?php

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\layout_builder\Routing\LayoutBuilderRouteEnhancer;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\layout_builder\Routing\LayoutBuilderRouteEnhancer
 * @group layout_builder
 */
class LayoutBuilderRouteEnhancerTest extends UnitTestCase {

  /**
   * @covers ::enhance
   */
  public function testEnhanceValidDefaults() {
    $route = new Route('/the/path', [], [], ['_layout_builder' => TRUE]);
    $route_enhancer = new LayoutBuilderRouteEnhancer();
    $defaults = [
      RouteObjectInterface::ROUTE_OBJECT => $route,
    ];
    // Ensure that the 'section_storage' key now contains the value stored for a
    // given entity type.
    $expected = [
      RouteObjectInterface::ROUTE_OBJECT => $route,
      'is_rebuilding' => TRUE,
    ];
    $result = $route_enhancer->enhance($defaults, new Request(['layout_is_rebuilding' => TRUE]));
    $this->assertEquals($expected, $result);

    $expected['is_rebuilding'] = FALSE;
    $result = $route_enhancer->enhance($defaults, new Request());
    $this->assertEquals($expected, $result);
  }

}
