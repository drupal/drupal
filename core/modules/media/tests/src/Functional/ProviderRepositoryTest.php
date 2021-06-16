<?php

namespace Drupal\Tests\media\Functional;

use Drupal\media\OEmbed\ProviderException;

/**
 * Tests the oEmbed provider repository.
 *
 * @covers \Drupal\media\OEmbed\ProviderRepository
 *
 * @group media
 */
class ProviderRepositoryTest extends MediaFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that provider discovery fails if the provider database is empty.
   *
   * @param string $content
   *   The expected JSON content of the provider database.
   *
   * @dataProvider providerEmptyProviderList
   */
  public function testEmptyProviderList($content) {
    $response = $this->prophesize('\GuzzleHttp\Psr7\Response');
    $response->getBody()->willReturn($content);

    $client = $this->createMock('\GuzzleHttp\Client');
    $client->method('request')->withAnyParameters()->willReturn($response->reveal());
    $this->container->set('http_client', $client);

    $this->expectException(ProviderException::class);
    $this->expectExceptionMessage('Remote oEmbed providers database returned invalid or empty list.');
    $this->container->get('media.oembed.provider_repository')->getAll();
  }

  /**
   * Data provider for testEmptyProviderList().
   *
   * @see ::testEmptyProviderList()
   *
   * @return array
   */
  public function providerEmptyProviderList() {
    return [
      'empty array' => ['[]'],
      'empty string' => [''],
    ];
  }

  /**
   * Tests that provider discovery fails with a non-existent provider database.
   *
   * @param string $providers_url
   *   The URL of the provider database.
   * @param string $exception_message
   *   The expected exception message.
   *
   * @dataProvider providerNonExistingProviderDatabase
   */
  public function testNonExistingProviderDatabase($providers_url, $exception_message) {
    $this->config('media.settings')
      ->set('oembed_providers_url', $providers_url)
      ->save();

    $this->expectException(ProviderException::class);
    $this->expectExceptionMessage($exception_message);
    $this->container->get('media.oembed.provider_repository')->getAll();
  }

  /**
   * Data provider for testEmptyProviderList().
   *
   * @see ::testEmptyProviderList()
   *
   * @return array
   */
  public function providerNonExistingProviderDatabase() {
    return [
      [
        'http://oembed1.com/providers.json',
        'Could not retrieve the oEmbed provider database from http://oembed1.com/providers.json',
      ],
      [
        'http://oembed.com/providers1.json',
        'Could not retrieve the oEmbed provider database from http://oembed.com/providers1.json',
      ],
    ];
  }

}
