<?php

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Routing\RequestFormatRouteFilter;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\Core\Routing\RequestFormatRouteFilter
 * @group Routing
 */
class RequestFormatRouteFilterTest extends UnitTestCase {

  /**
   * @covers ::filter
   * @dataProvider filterProvider
   */
  public function testFilter(RouteCollection $collection, $request_format, array $expected_filtered_collection) {
    $route_filter = new RequestFormatRouteFilter();

    $request = new Request();
    $request->setRequestFormat($request_format);
    $collection = $route_filter->filter($collection, $request);

    $this->assertCount(count($expected_filtered_collection), $collection);
    $this->assertSame($expected_filtered_collection, array_keys($collection->all()));
  }

  public function filterProvider() {
    $route_without_format = new Route('/test');
    $route_with_format = $route = new Route('/test');
    $route_with_format->setRequirement('_format', 'json');
    $route_with_multiple_formats = $route = new Route('/test');
    $route_with_multiple_formats->setRequirement('_format', 'json|xml');

    $collection = new RouteCollection();
    $collection->add('test_0', $route_without_format);
    $collection->add('test_1', $route_with_format);
    $collection->add('test_2', $route_with_multiple_formats);

    $sole_route_match_single_format = new RouteCollection();
    $sole_route_match_single_format->add('sole_route_single_format', $route_with_format);

    return [
      'nothing requested' => [clone $collection, '', ['test_0']],
      'xml requested' => [clone $collection, 'xml', ['test_2', 'test_0']],
      'json requested' => [clone $collection, 'json', ['test_1', 'test_2', 'test_0']],
      'html format requested' => [clone $collection, 'html', ['test_0']],
      'no format requested, defaults to html' => [clone $collection, NULL, ['test_0']],
      'no format requested, single route match with single format, defaults to that format' => [clone $sole_route_match_single_format, NULL, ['sole_route_single_format']],
    ];
  }

  /**
   * @covers ::filter
   */
  public function testNoRouteFound() {
    $url = $this->prophesize(GeneratedUrl::class);
    $url_assembler = $this->prophesize(UnroutedUrlAssemblerInterface::class);
    $url_assembler->assemble('http://localhost/test?_format=xml', ['query' => ['_format' => 'json'], 'external' => TRUE], TRUE)
      ->willReturn($url);
    $container = new ContainerBuilder();
    $container->set('unrouted_url_assembler', $url_assembler->reveal());
    \Drupal::setContainer($container);

    $collection = new RouteCollection();
    $route_with_format = $route = new Route('/test');
    $route_with_format->setRequirement('_format', 'json');
    $collection->add('test_0', $route_with_format);
    $collection->add('test_1', clone $route_with_format);

    $request = Request::create('test?_format=xml', 'GET');
    $request->setRequestFormat('xml');
    $route_filter = new RequestFormatRouteFilter();
    $this->expectException(NotAcceptableHttpException::class);
    $this->expectExceptionMessage('No route found for the specified format xml.');
    $route_filter->filter($collection, $request);
  }

  /**
   * @covers ::filter
   */
  public function testNoRouteFoundWhenNoRequestFormatAndSingleRouteWithMultipleFormats() {
    $this->expectException(NotAcceptableHttpException::class);
    $this->expectExceptionMessage('No route found for the specified format html.');

    $url = $this->prophesize(GeneratedUrl::class);
    $url_assembler = $this->prophesize(UnroutedUrlAssemblerInterface::class);
    $url_assembler->assemble('http://localhost/test', ['query' => ['_format' => 'json'], 'external' => TRUE], TRUE)
      ->willReturn($url);
    $url_assembler->assemble('http://localhost/test', ['query' => ['_format' => 'xml'], 'external' => TRUE], TRUE)
      ->willReturn($url);
    $container = new ContainerBuilder();
    $container->set('unrouted_url_assembler', $url_assembler->reveal());
    \Drupal::setContainer($container);

    $collection = new RouteCollection();
    $route_with_format = $route = new Route('/test');
    $route_with_format->setRequirement('_format', 'json|xml');
    $collection->add('sole_route_multiple_formats', $route_with_format);

    $request = Request::create('test', 'GET');
    $route_filter = new RequestFormatRouteFilter();
    $route_filter->filter($collection, $request);
  }

}
