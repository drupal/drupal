<?php

namespace Drupal\Tests\media\Unit;

use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\media\OEmbed\ProviderException;
use Drupal\media\OEmbed\ProviderRepository;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\media\OEmbed\ProviderRepository
 *
 * @group media
 */
class ProviderRepositoryTest extends UnitTestCase {

  /**
   * The provider repository under test.
   *
   * @var \Drupal\media\OEmbed\ProviderRepository
   */
  private $repository;

  /**
   * The HTTP client handler which will serve responses.
   *
   * @var \GuzzleHttp\Handler\MockHandler
   */
  private $responses;

  /**
   * The key-value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  private $keyValue;

  /**
   * The time that the current test began.
   *
   * @var int
   */
  private $currentTime;

  /**
   * The mocked logger channel.
   *
   * @var \Psr\Log\LoggerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  private $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $config_factory = $this->getConfigFactoryStub([
      'media.settings' => [
        'oembed_providers_url' => 'https://oembed.com/providers.json',
      ],
    ]);

    $key_value_factory = new KeyValueMemoryFactory();
    $this->keyValue = $key_value_factory->get('media');

    $this->currentTime = time();
    $time = $this->prophesize('\Drupal\Component\Datetime\TimeInterface');
    $time->getCurrentTime()->willReturn($this->currentTime);

    $this->logger = $this->prophesize('\Psr\Log\LoggerInterface');
    $logger_factory = new LoggerChannelFactory();
    $logger_factory->addLogger($this->logger->reveal());

    $this->responses = new MockHandler();
    $client = new Client([
      'handler' => HandlerStack::create($this->responses),
    ]);
    $this->repository = new ProviderRepository(
      $client,
      $config_factory,
      $time->reveal(),
      $key_value_factory,
      $logger_factory
    );
  }

  /**
   * Tests that a successful fetch stores the provider database in key-value.
   */
  public function testSuccessfulFetch(): void {
    $body = <<<END
[
  {
    "provider_name": "YouTube",
    "provider_url": "https:\/\/www.youtube.com\/",
    "endpoints": [
      {
        "schemes": [
          "https:\/\/*.youtube.com\/watch*",
          "https:\/\/*.youtube.com\/v\/*"
        ],
        "url": "https:\/\/www.youtube.com\/oembed",
        "discovery": true
      }
    ]
  }
]
END;
    $response = new Response(200, [], $body);
    $this->responses->append($response);

    $provider = $this->repository->get('YouTube');
    $stored_data = [
      'data' => [
        'YouTube' => $provider,
      ],
      'expires' => $this->currentTime + 604800,
    ];
    $this->assertSame($stored_data, $this->keyValue->get('oembed_providers'));
  }

  /**
   * Tests handling of invalid JSON when fetching the provider database.
   *
   * @param int $expiration_offset
   *   An offset to add to the current time to determine when the primed data,
   *   if any, expires.
   *
   * @dataProvider providerInvalidResponse
   */
  public function testInvalidResponse(int $expiration_offset): void {
    $provider = $this->prophesize('\Drupal\media\OEmbed\Provider')
      ->reveal();

    // This stored data should be returned, irrespective of whether it's fresh.
    $this->keyValue->set('oembed_providers', [
      'data' => [
        'YouTube' => $provider,
      ],
      'expires' => $this->currentTime + $expiration_offset,
    ]);

    $response = new Response(200, [], "This certainly isn't valid JSON.");
    $this->responses->append($response, $response);
    $this->assertSame($provider, $this->repository->get('YouTube'));

    // When there is no stored data, we should get an exception.
    $this->keyValue->delete('oembed_providers');
    $this->expectException(ProviderException::class);
    $this->repository->get('YouTube');
  }

  /**
   * Data provider for ::testInvalidResponse().
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerInvalidResponse(): array {
    return [
      'expired' => [
        -86400,
      ],
      'fresh' => [
        86400,
      ],
    ];
  }

  /**
   * Tests handling of exceptions when fetching the provider database.
   */
  public function testRequestException(): void {
    $provider = $this->prophesize('\Drupal\media\OEmbed\Provider')
      ->reveal();

    // This data is expired (stale), but it should be returned anyway.
    $this->keyValue->set('oembed_providers', [
      'data' => [
        'YouTube' => $provider,
      ],
      'expires' => $this->currentTime - 86400,
    ]);

    $response = new Response(503);
    $this->responses->append($response, $response);
    $this->assertSame($provider, $this->repository->get('YouTube'));

    // When there is no stored data, we should get an exception.
    $this->keyValue->delete('oembed_providers');
    $this->expectException(ProviderException::class);
    $this->repository->get('YouTube');
  }

  /**
   * Tests a successful fetch but with a single corrupt item.
   */
  public function testCorruptProviderIgnored(): void {
    $body = <<<END
[
  {
    "provider_name": "YouTube",
    "provider_url": "https:\/\/www.youtube.com\/",
    "endpoints": [
      {
        "schemes": [
          "https:\/\/*.youtube.com\/watch*",
          "https:\/\/*.youtube.com\/v\/*"
        ],
        "url": "https:\/\/www.youtube.com\/oembed",
        "discovery": true
      }
    ]
  },
  {
    "provider_name": "Uncle Rico's football videos",
    "provider_url": "not a real url",
    "endpoints": []
  }
]
END;
    $response = new Response(200, [], $body);
    $this->responses->append($response);

    // The corrupt provider should cause a warning to be logged.
    $this->logger->log(
      RfcLogLevel::WARNING,
      "Provider Uncle Rico's football videos does not define a valid external URL.",
      Argument::type('array')
    )->shouldBeCalled();

    $youtube = $this->repository->get('YouTube');
    // The corrupt provider should not be stored.
    $stored_data = [
      'data' => [
        'YouTube' => $youtube,
      ],
      'expires' => $this->currentTime + 604800,
    ];
    $this->assertSame($stored_data, $this->keyValue->get('oembed_providers'));

    $this->expectException('InvalidArgumentException');
    $this->repository->get("Uncle Rico's football videos");
  }

}
