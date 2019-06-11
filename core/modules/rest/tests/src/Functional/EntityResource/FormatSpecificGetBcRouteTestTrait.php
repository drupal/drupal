<?php

namespace Drupal\Tests\rest\Functional\EntityResource;

use Drupal\Core\Url;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Provides test methods to assert BC on format-specific GET routes.
 *
 * @internal
 */
trait FormatSpecificGetBcRouteTestTrait {

  /**
   * @group legacy
   *
   * @see \Drupal\rest\RouteProcessor\RestResourceGetRouteProcessorBC
   */
  public function testFormatSpecificGetBcRoute() {
    $this->provisionEntityResource();
    $url = $this->getEntityResourceUrl();

    // BC: Format-specific GET routes are deprecated. They are available on both
    // new and old sites, but trigger deprecation notices.
    $bc_route = Url::fromRoute('rest.entity.' . static::$entityTypeId . '.GET.' . static::$format, $url->getRouteParameters(), $url->getOptions());
    $bc_route->setUrlGenerator($this->container->get('url_generator'));
    $this->expectDeprecation(sprintf("The 'rest.entity.entity_test.GET.%s' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_test.GET' route instead.", static::$format));
    $this->assertSame($url->toString(TRUE)->getGeneratedUrl(), $bc_route->toString(TRUE)->getGeneratedUrl());
  }

  /**
   * @group legacy
   *
   * @see \Drupal\rest\Plugin\ResourceBase::routes
   */
  public function testNoFormatSpecificGetBcRouteForOtherFormats() {
    $this->expectException(RouteNotFoundException::class);

    $this->provisionEntityResource();
    $url = $this->getEntityResourceUrl();

    // Verify no format-specific GET BC routes are created for other formats.
    $other_format = static::$format === 'json' ? 'xml' : 'json';
    $bc_route_other_format = Url::fromRoute('rest.entity.entity_test.GET.' . $other_format, $url->getRouteParameters(), $url->getOptions());
    $bc_route_other_format->setUrlGenerator($this->container->get('url_generator'));
    $bc_route_other_format->toString(TRUE);
  }

}
