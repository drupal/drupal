<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\Routing\LayoutTempstoreRouteEnhancer;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Tests Drupal\layout_builder\Routing\LayoutTempstoreRouteEnhancer.
 */
#[CoversClass(LayoutTempstoreRouteEnhancer::class)]
#[Group('layout_builder')]
class LayoutTempstoreRouteEnhancerTest extends UnitTestCase {

  /**
   * Tests enhance.
   */
  public function testEnhance(): void {
    $section_storage = $this->prophesize(SectionStorageInterface::class);
    $layout_tempstore_repository = $this->prophesize(LayoutTempstoreRepositoryInterface::class);
    $layout_tempstore_repository->get($section_storage->reveal())->willReturn('the_return_value');

    $options = [
      'parameters' => [
        'section_storage' => [
          'layout_builder_tempstore' => TRUE,
        ],
      ],
    ];
    $route = new Route('/test/{id}/{literal}/{null}', [], [], $options);

    $defaults = [
      'section_storage' => $section_storage->reveal(),
      RouteObjectInterface::ROUTE_OBJECT => $route,
    ];

    $expected = [
      'section_storage' => 'the_return_value',
      RouteObjectInterface::ROUTE_OBJECT => $route,
    ];

    $enhancer = new LayoutTempstoreRouteEnhancer($layout_tempstore_repository->reveal());
    $result = $enhancer->enhance($defaults, new Request());
    $this->assertEquals($expected, $result);
  }

}
