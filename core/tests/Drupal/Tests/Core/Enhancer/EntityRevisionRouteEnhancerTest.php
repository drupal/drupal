<?php

namespace Drupal\Tests\Core\Enhancer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\Enhancer\EntityRevisionRouteEnhancer;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Routing\Enhancer\EntityRevisionRouteEnhancer
 * @group Entity
 */
class EntityRevisionRouteEnhancerTest extends UnitTestCase {

  /**
   * @var \Drupal\entity\RouteEnhancer\EntityRevisionRouteEnhancer
   */
  protected $routeEnhancer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->routeEnhancer = new EntityRevisionRouteEnhancer();
  }

  /**
   * @covers ::applies
   * @dataProvider providerTestApplies
   */
  public function testApplies(Route $route, $expected) {
    $this->assertEquals($expected, $this->routeEnhancer->applies($route));
  }

  public function providerTestApplies() {
    $data = [];
    $data['no-parameter'] = [new Route('/test-path'), FALSE];
    $data['none-revision-parameters'] = [new Route('/test-path/{entity_test}', [], [], ['parameters' => ['entity_test' => ['type' => 'entity:entity_test']]]), FALSE];
    $data['with-revision-parameter'] = [new Route('/test-path/{entity_test_revision}', [], [], ['parameters' => ['entity_test_revision' => ['type' => 'entity_revision:entity_test']]]), TRUE];

    return $data;
  }

  /**
   * @covers ::enhance
   */
  public function testEnhanceWithoutEntityRevision() {
    $route = new Route('/test-path/{entity_test}', [], [], ['parameters' => ['entity_test' => ['type' => 'entity:entity_test']]]);
    $request = Request::create('/test-path/123');
    $entity = $this->prophesize(EntityInterface::class);

    $defaults = [];
    $defaults['entity_test'] = $entity->reveal();
    $defaults[RouteObjectInterface::ROUTE_OBJECT] = $route;
    $this->assertEquals($defaults, $this->routeEnhancer->enhance($defaults, $request));
  }

  /**
   * @covers ::enhance
   */
  public function testEnhanceWithEntityRevision() {
    $route = new Route('/test-path/{entity_test_revision}', [], [], ['parameters' => ['entity_test_revision' => ['type' => 'entity_revision:entity_test']]]);
    $request = Request::create('/test-path/123');
    $entity = $this->prophesize(EntityInterface::class);

    $defaults = [];
    $defaults['entity_test_revision'] = $entity->reveal();
    $defaults[RouteObjectInterface::ROUTE_OBJECT] = $route;

    $expected = $defaults;
    $expected['_entity_revision'] = $defaults['entity_test_revision'];
    $this->assertEquals($expected, $this->routeEnhancer->enhance($defaults, $request));
  }

}
