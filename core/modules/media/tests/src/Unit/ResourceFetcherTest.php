<?php

namespace Drupal\Tests\media\Unit;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Serialization\SerializationInterface;
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
    $response = new Response(200, $headers, $body);

    $mock_handler = new MockHandler([
      $response,
      $response,
    ]);
    $client = new Client([
      'handler' => HandlerStack::create($mock_handler),
    ]);
    $providers = $this->prophesize('\Drupal\media\OEmbed\ProviderRepositoryInterface')->reveal();

    // Create a fallback decoder that will record the original string of JSON,
    // so we can be sure this decoder actually got used.
    $fallback_decoder = new class () extends Json {
      public static $decodedString;

      public static function decode($raw) {
        static::$decodedString = $raw;
        return parent::decode($raw);
      }
    };

    $fetcher = new ResourceFetcher($client, $providers, NULL, $fallback_decoder);
    $fetcher->fetchResource('test');
    $this->assertSame((string) $response->getBody(), $fallback_decoder::$decodedString);

    // Use a fallback decoder that throws an exception, so we can ensure that
    // it gets wrapped and re-thrown as a ResourceException.
    $fetcher = new ResourceFetcher($client, $providers, NULL, new class () implements SerializationInterface {
      /**
       * {@inheritdoc}
       */
      public static function encode($data) {
      }

      /**
       * {@inheritdoc}
       */
      public static function getFileExtension() {
      }

      /**
       * {@inheritdoc}
       */
      public static function decode($string) {
        throw new InvalidDataTypeException('I promise I will screw up your day.');
      }
    });
    $this->expectException('\Drupal\media\OEmbed\ResourceException');
    $this->expectExceptionMessage('I promise I will screw up your day.');
    $fetcher->fetchResource('test');
  }

}
