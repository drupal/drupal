<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Unit\Routing;

use Drupal\Core\Entity\EntityInterface;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRelationship;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository;
use Drupal\jsonapi\Routing\Routes;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\jsonapi\Routing\Routes
 * @group jsonapi
 *
 * @internal
 */
class RoutesTest extends UnitTestCase {

  /**
   * List of routes objects for the different scenarios.
   *
   * @var \Drupal\jsonapi\Routing\Routes[]
   */
  protected $routes;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $relationship_fields = [
      'external' => new ResourceTypeRelationship('external'),
      'internal' => new ResourceTypeRelationship('internal'),
      'both' => new ResourceTypeRelationship('both'),
    ];
    $type_1 = new ResourceType('entity_type_1', 'bundle_1_1', EntityInterface::class, FALSE, TRUE, TRUE, FALSE, $relationship_fields);
    $type_2 = new ResourceType('entity_type_2', 'bundle_2_1', EntityInterface::class, TRUE, TRUE, TRUE, FALSE, $relationship_fields);
    $relatable_resource_types = [
      'external' => [$type_1],
      'internal' => [$type_2],
      'both' => [$type_1, $type_2],
    ];
    $type_1->setRelatableResourceTypes($relatable_resource_types);
    $type_2->setRelatableResourceTypes($relatable_resource_types);
    // This type ensures that we can create routes for bundle IDs which might be
    // cast from strings to integers.  It should not affect related resource
    // routing.
    $type_3 = new ResourceType('entity_type_3', '123', EntityInterface::class, TRUE);
    $type_3->setRelatableResourceTypes([]);
    $resource_type_repository = $this->prophesize(ResourceTypeRepository::class);
    $resource_type_repository->all()->willReturn([$type_1, $type_2, $type_3]);
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('jsonapi.resource_type.repository')->willReturn($resource_type_repository->reveal());
    $container->getParameter('jsonapi.base_path')->willReturn('/jsonapi');
    $container->getParameter('authentication_providers')->willReturn([
      'lorem' => [],
      'ipsum' => [],
    ]);

    $this->routes['ok'] = Routes::create($container->reveal());
  }

  /**
   * @covers ::routes
   */
  public function testRoutesCollection(): void {
    // Get the route collection and start making assertions.
    $routes = $this->routes['ok']->routes();

    // - 2 collection routes; GET & POST for the non-internal resource type.
    // - 3 individual routes; GET, PATCH & DELETE for the non-internal resource
    //   type.
    // - 2 related routes; GET for the non-internal resource type relationships
    //   fields: external & both.
    // - 12 relationship routes; 3 fields * 4 HTTP methods.
    //   `relationship` routes are generated even for internal target resource
    //   types (`related` routes are not).
    // - 1 for the JSON:API entry point.
    $this->assertEquals(20, $routes->count());

    $iterator = $routes->getIterator();
    // Check the collection route.
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $iterator->offsetGet('jsonapi.entity_type_1--bundle_1_1.collection');
    $this->assertSame('/jsonapi/entity_type_1/bundle_1_1', $route->getPath());
    $this->assertSame(['lorem', 'ipsum'], $route->getOption('_auth'));
    $this->assertSame('entity_type_1--bundle_1_1', $route->getDefault(Routes::RESOURCE_TYPE_KEY));
    $this->assertEquals(['GET'], $route->getMethods());
    $this->assertSame(Routes::CONTROLLER_SERVICE_NAME . ':getCollection', $route->getDefault(RouteObjectInterface::CONTROLLER_NAME));
    // Check the collection POST route.
    $route = $iterator->offsetGet('jsonapi.entity_type_1--bundle_1_1.collection.post');
    $this->assertSame('/jsonapi/entity_type_1/bundle_1_1', $route->getPath());
    $this->assertSame(['lorem', 'ipsum'], $route->getOption('_auth'));
    $this->assertSame('entity_type_1--bundle_1_1', $route->getDefault(Routes::RESOURCE_TYPE_KEY));
    $this->assertEquals(['POST'], $route->getMethods());
    $this->assertSame(Routes::CONTROLLER_SERVICE_NAME . ':createIndividual', $route->getDefault(RouteObjectInterface::CONTROLLER_NAME));
  }

  /**
   * @covers ::routes
   */
  public function testRoutesIndividual(): void {
    // Get the route collection and start making assertions.
    $iterator = $this->routes['ok']->routes()->getIterator();

    // Check the individual route.
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $iterator->offsetGet('jsonapi.entity_type_1--bundle_1_1.individual');
    $this->assertSame('/jsonapi/entity_type_1/bundle_1_1/{entity}', $route->getPath());
    $this->assertSame('entity_type_1--bundle_1_1', $route->getDefault(Routes::RESOURCE_TYPE_KEY));
    $this->assertEquals(['GET'], $route->getMethods());
    $this->assertSame(Routes::CONTROLLER_SERVICE_NAME . ':getIndividual', $route->getDefault(RouteObjectInterface::CONTROLLER_NAME));
    $this->assertSame(['lorem', 'ipsum'], $route->getOption('_auth'));
    $this->assertEquals([
      'entity' => ['type' => 'entity:entity_type_1'],
      'resource_type' => ['type' => 'jsonapi_resource_type'],
    ], $route->getOption('parameters'));

    $route = $iterator->offsetGet('jsonapi.entity_type_1--bundle_1_1.individual.patch');
    $this->assertSame('/jsonapi/entity_type_1/bundle_1_1/{entity}', $route->getPath());
    $this->assertSame('entity_type_1--bundle_1_1', $route->getDefault(Routes::RESOURCE_TYPE_KEY));
    $this->assertEquals(['PATCH'], $route->getMethods());
    $this->assertSame(Routes::CONTROLLER_SERVICE_NAME . ':patchIndividual', $route->getDefault(RouteObjectInterface::CONTROLLER_NAME));
    $this->assertSame(['lorem', 'ipsum'], $route->getOption('_auth'));
    $this->assertEquals([
      'entity' => ['type' => 'entity:entity_type_1'],
      'resource_type' => ['type' => 'jsonapi_resource_type'],
    ], $route->getOption('parameters'));

    $route = $iterator->offsetGet('jsonapi.entity_type_1--bundle_1_1.individual.delete');
    $this->assertSame('/jsonapi/entity_type_1/bundle_1_1/{entity}', $route->getPath());
    $this->assertSame('entity_type_1--bundle_1_1', $route->getDefault(Routes::RESOURCE_TYPE_KEY));
    $this->assertEquals(['DELETE'], $route->getMethods());
    $this->assertSame(Routes::CONTROLLER_SERVICE_NAME . ':deleteIndividual', $route->getDefault(RouteObjectInterface::CONTROLLER_NAME));
    $this->assertSame(['lorem', 'ipsum'], $route->getOption('_auth'));
    $this->assertEquals([
      'entity' => ['type' => 'entity:entity_type_1'],
      'resource_type' => ['type' => 'jsonapi_resource_type'],
    ], $route->getOption('parameters'));
  }

  /**
   * @covers ::routes
   */
  public function testRoutesRelated(): void {
    // Get the route collection and start making assertions.
    $iterator = $this->routes['ok']->routes()->getIterator();

    // Check the related route.
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $iterator->offsetGet('jsonapi.entity_type_1--bundle_1_1.external.related');
    $this->assertSame('/jsonapi/entity_type_1/bundle_1_1/{entity}/external', $route->getPath());
    $this->assertSame('entity_type_1--bundle_1_1', $route->getDefault(Routes::RESOURCE_TYPE_KEY));
    $this->assertEquals(['GET'], $route->getMethods());
    $this->assertSame(Routes::CONTROLLER_SERVICE_NAME . ':getRelated', $route->getDefault(RouteObjectInterface::CONTROLLER_NAME));
    $this->assertSame(['lorem', 'ipsum'], $route->getOption('_auth'));
    $this->assertEquals([
      'entity' => ['type' => 'entity:entity_type_1'],
      'resource_type' => ['type' => 'jsonapi_resource_type'],
    ], $route->getOption('parameters'));
  }

  /**
   * @covers ::routes
   */
  public function testRoutesRelationships(): void {
    // Get the route collection and start making assertions.
    $iterator = $this->routes['ok']->routes()->getIterator();

    // Check the relationships route.
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $iterator->offsetGet('jsonapi.entity_type_1--bundle_1_1.both.relationship.get');
    $this->assertSame('/jsonapi/entity_type_1/bundle_1_1/{entity}/relationships/both', $route->getPath());
    $this->assertSame('entity_type_1--bundle_1_1', $route->getDefault(Routes::RESOURCE_TYPE_KEY));
    $this->assertEquals(['GET'], $route->getMethods());
    $this->assertSame(Routes::CONTROLLER_SERVICE_NAME . ':getRelationship', $route->getDefault(RouteObjectInterface::CONTROLLER_NAME));
    $this->assertSame(['lorem', 'ipsum'], $route->getOption('_auth'));
    $this->assertEquals([
      'entity' => ['type' => 'entity:entity_type_1'],
      'resource_type' => ['type' => 'jsonapi_resource_type'],
    ], $route->getOption('parameters'));
  }

  /**
   * Ensures that the expected routes are created or not created.
   *
   * @dataProvider expectedRoutes
   */
  public function testRoutes($route): void {
    $this->assertArrayHasKey($route, $this->routes['ok']->routes()->all());
  }

  /**
   * Lists routes which should have been created.
   */
  public static function expectedRoutes() {
    return [
      ['jsonapi.entity_type_1--bundle_1_1.individual'],
      ['jsonapi.entity_type_1--bundle_1_1.collection'],
      ['jsonapi.entity_type_1--bundle_1_1.internal.relationship.get'],
      ['jsonapi.entity_type_1--bundle_1_1.internal.relationship.post'],
      ['jsonapi.entity_type_1--bundle_1_1.internal.relationship.patch'],
      ['jsonapi.entity_type_1--bundle_1_1.internal.relationship.delete'],
      ['jsonapi.entity_type_1--bundle_1_1.external.related'],
      ['jsonapi.entity_type_1--bundle_1_1.external.relationship.get'],
      ['jsonapi.entity_type_1--bundle_1_1.external.relationship.post'],
      ['jsonapi.entity_type_1--bundle_1_1.external.relationship.patch'],
      ['jsonapi.entity_type_1--bundle_1_1.external.relationship.delete'],
      ['jsonapi.entity_type_1--bundle_1_1.both.related'],
      ['jsonapi.entity_type_1--bundle_1_1.both.relationship.get'],
      ['jsonapi.entity_type_1--bundle_1_1.both.relationship.post'],
      ['jsonapi.entity_type_1--bundle_1_1.both.relationship.patch'],
      ['jsonapi.entity_type_1--bundle_1_1.both.relationship.delete'],
      ['jsonapi.resource_list'],
    ];
  }

  /**
   * Ensures that no routes are created for internal resources.
   *
   * @dataProvider notExpectedRoutes
   */
  public function testInternalRoutes($route): void {
    $this->assertArrayNotHasKey($route, $this->routes['ok']->routes()->all());
  }

  /**
   * Lists routes which should have been created.
   */
  public static function notExpectedRoutes() {
    return [
      ['jsonapi.entity_type_2--bundle_2_1.individual'],
      ['jsonapi.entity_type_2--bundle_2_1.collection'],
      ['jsonapi.entity_type_2--bundle_2_1.collection.post'],
      ['jsonapi.entity_type_2--bundle_2_1.internal.related'],
      ['jsonapi.entity_type_2--bundle_2_1.internal.relationship'],
    ];
  }

}
