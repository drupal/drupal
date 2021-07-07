<?php

namespace Drupal\Tests\media\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\media\OEmbed\ResourceFetcher;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
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
    $referer = $request->getHttpHost();
    $this->assertNotEmpty($referer);

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
      $this->prophesize('\Drupal\media\OEmbed\ProviderRepositoryInterface')->reveal(),
      $request_stack
    );

    // Ensure that the resource fetcher will send the Referer header to the
    // oEmbed provider when fetching resource data.
    $fetcher->fetchResource('https://example.com/fake/resource.json');
    $this->assertNotEmpty($history);
    $this->assertSame($referer, $history[0]['request']->getHeaderLine('Referer'));
  }

}
