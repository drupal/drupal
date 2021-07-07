<?php

namespace Drupal\Tests\media\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\NullBackend;
use Drupal\media\OEmbed\ResourceFetcher;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\media\OEmbed\ResourceFetcher
 *
 * @group media
 */
class ResourceFetcherTest extends UnitTestCase {

  /**
   * Tests that the resource fetcher sends a Referer header.
   */
  public function testReferer(): void {
    // Mock a response containing fake resource data.
    $headers = [
      'Content-Type' => ['application/json'],
    ];
    $body = Json::encode([
      'version' => '1.0',
      'type' => 'rich',
      'html' => 'Fake resource',
    ]);
    $response = new Response(200, $headers, $body);

    // Create a request so that we actually have a referer to send.
    $request = Request::create('https://example.com');
    $referer = $request->getSchemeAndHttpHost();
    $this->assertNotEmpty('https://example.com', $referer);

    $request_stack = new RequestStack();
    $request_stack->push($request);

    // Prepare a mocked HTTP client which will remember the requests and
    // responses for later analysis.
    $handler = new MockHandler([$response]);
    $handler_stack = HandlerStack::create($handler);
    $history = [];
    $middleware = Middleware::history($history);
    $handler_stack->push($middleware);

    $fetcher = new ResourceFetcher(
      new Client(['handler' => $handler_stack]),
      $this->createMock('\Drupal\media\OEmbed\ProviderRepositoryInterface'),
      $request_stack
    );

    // Ensure that the resource fetcher will send the Referer header to the
    // oEmbed provider when fetching resource data.
    $fetcher->fetchResource('https://example.com/fake/resource.json');
    $this->assertNotEmpty($history);
    $this->assertSame($referer, $history[0]['request']->getHeaderLine('Referer'));
  }

  /**
   * Tests deprecation messages when constructing a ResourceFetcher.
   *
   * @group legacy
   */
  public function testParameterDeprecations(): void {
    $http_client = new Client();
    $providers = $this->createMock('\Drupal\media\OEmbed\ProviderRepositoryInterface');

    $container = new ContainerBuilder();
    $container->set('request_stack', new RequestStack());
    \Drupal::setContainer($container);

    $this->expectDeprecation('Passing NULL for the $request_stack parameter to ' . ResourceFetcher::class . '::__construct() is deprecated in drupal:9.3.0 and will be required in drupal:10.0.0. See https://www.drupal.org/project/drupal/issues/3056124');
    new ResourceFetcher($http_client, $providers);

    $this->expectDeprecation('Passing an instance of CacheBackendInterface in the $request_stack parameter to ' . ResourceFetcher::class . '::__construct() is deprecated in drupal:9.3.0 and removed in drupal:10.0.0. Pass a \Symfony\Component\HttpFoundation\RequestStack object instead. See https://www.drupal.org/project/drupal/issues/3056124');
    new ResourceFetcher($http_client, $providers, new NullBackend('foo'));
  }

}
