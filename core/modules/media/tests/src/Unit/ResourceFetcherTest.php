<?php

namespace Drupal\Tests\media\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\media\OEmbed\ResourceFetcher;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * @group media
 *
 * @coversDefaultClass \Drupal\media\OEmbed\ResourceFetcher
 */
class ResourceFetcherTest extends UnitTestCase {

  /**
   * Tests how the resource fetcher handles unknown Content-Type headers.
   *
   * @covers ::fetchResource
   */
  public function testUnknownContentTypeHeader(): void {
    $headers = [
      'Content-Type' => ['text/html'],
    ];
    $body = Json::encode([
      'version' => '1.0',
      'type' => 'video',
      'html' => 'test',
    ]);
    $valid_response = new Response(200, $headers, $body);
    // Strip off the trailing '}' to produce a response that will cause a JSON
    // parse error.
    $invalid_response = new Response(200, $headers, rtrim($body, '}'));

    $mock_handler = new MockHandler([$valid_response, $invalid_response]);
    $client = new Client([
      'handler' => HandlerStack::create($mock_handler),
    ]);
    $providers = $this->createMock('\Drupal\media\OEmbed\ProviderRepositoryInterface');

    $fetcher = new ResourceFetcher($client, $providers);
    /** @var \Drupal\media\OEmbed\Resource $resource */
    $resource = $fetcher->fetchResource('test');
    // The resource should have been successfully decoded as JSON.
    $this->assertSame('video', $resource->getType());
    $this->assertSame('test', $resource->getHtml());

    // Invalid JSON should throw an exception.
    $this->expectException('\Drupal\media\OEmbed\ResourceException');
    $this->expectExceptionMessage('Error decoding oEmbed resource: Syntax error');
    $fetcher->fetchResource('test');
  }

}
