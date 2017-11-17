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
   * @covers ::applies
   * @dataProvider providerTestApplies
   */
  public function testApplies($defaults, $options, $expected) {
    $route_enhancer = new LayoutBuilderRouteEnhancer();
    $route = new Route('/some/path', $defaults, [], $options);

    $reflection_method = new \ReflectionMethod($route_enhancer, 'applies');
    $reflection_method->setAccessible(TRUE);
    $result = $reflection_method->invoke($route_enhancer, $route);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides test data for ::testApplies().
   */
  public function providerTestApplies() {
    $data = [];
    $data['layout_builder_true'] = [
      ['entity_type_id' => 'the_entity_type'],
      ['_layout_builder' => TRUE],
      TRUE,
    ];
    $data['layout_builder_false'] = [
      ['entity_type_id' => 'the_entity_type'],
      ['_layout_builder' => FALSE],
      FALSE,
    ];
    $data['layout_builder_null'] = [
      ['entity_type_id' => 'the_entity_type'],
      ['_layout_builder' => NULL],
      FALSE,
    ];
    $data['entity_type_id_empty'] = [
      ['entity_type_id' => ''],
      ['_layout_builder' => TRUE],
      FALSE,
    ];
    $data['no_entity_type_id'] = [
      [],
      ['_layout_builder' => TRUE],
      FALSE,
    ];
    $data['no_layout_builder'] = [
      ['entity_type_id' => 'the_entity_type'],
      [],
      FALSE,
    ];
    $data['empty'] = [
      [],
      [],
      FALSE,
    ];
    return $data;
  }

  /**
   * @covers ::enhance
   */
  public function testEnhanceValidDefaults() {
    $route = new Route('/the/path', ['entity_type_id' => 'the_entity_type'], [], ['_layout_builder' => TRUE]);
    $route_enhancer = new LayoutBuilderRouteEnhancer();
    $object = new \stdClass();
    $defaults = [
      'entity_type_id' => 'the_entity_type',
      'the_entity_type' => $object,
      RouteObjectInterface::ROUTE_NAME => 'the_route_name',
      RouteObjectInterface::ROUTE_OBJECT => $route,
    ];
    // Ensure that the 'entity' key now contains the value stored for a given
    // entity type.
    $expected = [
      'entity_type_id' => 'the_entity_type',
      'the_entity_type' => $object,
      RouteObjectInterface::ROUTE_NAME => 'the_route_name',
      RouteObjectInterface::ROUTE_OBJECT => $route,
      'entity' => $object,
      'is_rebuilding' => TRUE,
    ];
    $result = $route_enhancer->enhance($defaults, new Request(['layout_is_rebuilding' => TRUE]));
    $this->assertEquals($expected, $result);

    $expected['is_rebuilding'] = FALSE;
    $result = $route_enhancer->enhance($defaults, new Request());
    $this->assertEquals($expected, $result);
    $this->assertSame($object, $result['entity']);

    // Modifying the original value updates the 'entity' copy.
    $result['the_entity_type'] = 'something else';
    $this->assertSame('something else', $result['entity']);
  }

  /**
   * @covers ::enhance
   */
  public function testEnhanceMissingEntity() {
    $route_enhancer = new LayoutBuilderRouteEnhancer();
    $route = new Route('/the/path', ['entity_type_id' => 'the_entity_type'], [], ['_layout_builder' => TRUE]);
    $defaults = [
      RouteObjectInterface::ROUTE_NAME => 'the_route',
      RouteObjectInterface::ROUTE_OBJECT => $route,
      'entity_type_id' => 'the_entity_type',
    ];
    $this->setExpectedException(\RuntimeException::class, 'Failed to find the "the_entity_type" entity in route named the_route');
    $route_enhancer->enhance($defaults, new Request());
  }

  /**
   * Provides test data for ::testEnhanceException().
   */
  public function providerTestEnhanceException() {
    $data = [];
    $data['missing_entity'] = [
      [
        RouteObjectInterface::ROUTE_NAME => 'the_route',
        'entity_type_id' => 'the_entity_type',
      ],
      'Failed to find the "the_entity_type" entity in route named the_route',
    ];
    $data['missing_entity_type_id'] = [
      [
        RouteObjectInterface::ROUTE_NAME => 'the_route',
      ],
      'Failed to find an entity type ID in route named the_route',
    ];
    return $data;
  }

}
