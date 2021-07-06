<?php

namespace Drupal\Tests\media\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\media\OEmbed\ResourceFetcher;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Prophecy\Argument;
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
    $http_client = $this->prophesize('\GuzzleHttp\ClientInterface');

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
    $request = Request::createFromGlobals();
    $referer = $request->getHttpHost();
    $this->assertNotEmpty($referer);
    $options = [
      RequestOptions::HEADERS => [
        'Referer' => $referer,
      ],
    ];
    $http_client->request('GET', Argument::type('string'), $options)
      ->willReturn($response);

    $request_stack = new RequestStack();
    $request_stack->push($request);

    $fetcher = new ResourceFetcher(
      $http_client->reveal(),
      $this->prophesize('\Drupal\media\OEmbed\ProviderRepositoryInterface')->reveal(),
      $request_stack
    );
    $fetcher->fetchResource('https://example.com/oembed/resource.json');
  }

}
