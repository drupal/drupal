<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Enhancer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\Enhancer\EntityRevisionRouteEnhancer;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Tests Drupal\Core\Routing\Enhancer\EntityRevisionRouteEnhancer.
 */
#[CoversClass(EntityRevisionRouteEnhancer::class)]
#[Group('Entity')]
class EntityRevisionRouteEnhancerTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\Routing\Enhancer\EntityRevisionRouteEnhancer
   */
  protected $routeEnhancer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->routeEnhancer = new EntityRevisionRouteEnhancer();
  }

  /**
   * Tests enhance without parameter.
   *
   * @legacy-covers ::enhance
   */
  public function testEnhanceWithoutParameter(): void {
    $route = new Route('/test-path/{entity_test}');

    $request = Request::create('/test-path');

    $defaults = [];
    $defaults[RouteObjectInterface::ROUTE_OBJECT] = $route;
    $this->assertEquals($defaults, $this->routeEnhancer->enhance($defaults, $request));
  }

  /**
   * Tests enhance without entity revision.
   *
   * @legacy-covers ::enhance
   */
  public function testEnhanceWithoutEntityRevision(): void {
    $route = new Route('/test-path/{entity_test}', [], [], ['parameters' => ['entity_test' => ['type' => 'entity:entity_test']]]);
    $request = Request::create('/test-path/123');
    $entity = $this->prophesize(EntityInterface::class);

    $defaults = [];
    $defaults['entity_test'] = $entity->reveal();
    $defaults[RouteObjectInterface::ROUTE_OBJECT] = $route;
    $this->assertEquals($defaults, $this->routeEnhancer->enhance($defaults, $request));
  }

  /**
   * Tests enhance with entity revision.
   *
   * @legacy-covers ::enhance
   */
  public function testEnhanceWithEntityRevision(): void {
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
