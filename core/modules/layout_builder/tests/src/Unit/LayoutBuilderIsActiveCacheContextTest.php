<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\layout_builder\Cache\LayoutBuilderIsActiveCacheContext;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\layout_builder\Cache\LayoutBuilderIsActiveCacheContext
 *
 * @group layout_builder
 */
class LayoutBuilderIsActiveCacheContextTest extends UnitTestCase {

  /**
   * @covers ::getContext
   */
  public function testGetContextMissingEntityTypeId() {
    $route_match = $this->prophesize(RouteMatchInterface::class);
    $cache_context = new LayoutBuilderIsActiveCacheContext($route_match->reveal());
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Missing entity type ID');
    $cache_context->getContext();
  }

  /**
   * @covers ::getContext
   * @covers ::getDisplay
   */
  public function testGetContextNonFieldableEntity() {
    $route_match = $this->prophesize(RouteMatchInterface::class);
    $route_match->getParameter('not_a_fieldable_entity')->willReturn('something that is not a fieldable entity');

    $cache_context = new LayoutBuilderIsActiveCacheContext($route_match->reveal());
    $expected = '0';
    $actual = $cache_context->getContext('not_a_fieldable_entity');
    $this->assertSame($expected, $actual);
  }

  /**
   * @covers ::getContext
   * @covers ::getDisplay
   *
   * @dataProvider providerTestGetContext
   */
  public function testGetContext($is_overridden, $expected) {
    $entity_display = $this->prophesize(LayoutEntityDisplayInterface::class);
    $entity_display->isOverridable()->willReturn($is_overridden);

    $entity_type_id = 'a_fieldable_entity_type';
    $fieldable_entity = $this->prophesize(FieldableEntityInterface::class);
    $fieldable_entity->getEntityTypeId()->willReturn($entity_type_id);
    $fieldable_entity->bundle()->willReturn('the_bundle_id');

    $route_match = $this->prophesize(RouteMatchInterface::class);
    $route_match->getParameter($entity_type_id)->willReturn($fieldable_entity->reveal());

    // \Drupal\Core\Entity\Entity\EntityViewDisplay::collectRenderDisplay() is a
    // static method and can not be mocked on its own. All of the expectations
    // of that method are mocked in the next code block.
    $entity_query = $this->prophesize(QueryInterface::class);
    $entity_query->condition(Argument::cetera())->willReturn($entity_query);
    $entity_query->execute()->willReturn([
      'a_fieldable_entity_type.the_bundle_id.full' => 'a_fieldable_entity_type.the_bundle_id.full',
    ]);
    $entity_storage = $this->prophesize(EntityStorageInterface::class);
    $entity_storage->getQuery('AND')->willReturn($entity_query->reveal());
    $entity_storage->loadMultiple(Argument::type('array'))->willReturn([
      'a_fieldable_entity_type.the_bundle_id.full' => $entity_display->reveal(),
    ]);
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('entity_view_display')->willReturn($entity_storage->reveal());
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager->reveal());
    $container->set('module_handler', $module_handler->reveal());
    \Drupal::setContainer($container);

    $cache_context = new LayoutBuilderIsActiveCacheContext($route_match->reveal());
    $actual = $cache_context->getContext($entity_type_id);
    $this->assertSame($expected, $actual);
  }

  /**
   * Provides test data for ::testGetContext().
   */
  public function providerTestGetContext() {
    $data = [];
    $data['overridden'] = [
      TRUE,
      '1',
    ];
    $data['not overridden'] = [
      FALSE,
      '0',
    ];
    return $data;
  }

}
