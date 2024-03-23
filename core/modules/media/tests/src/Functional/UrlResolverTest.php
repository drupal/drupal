<?php

namespace Drupal\Tests\media\Functional;

use Drupal\Tests\media\Traits\OEmbedTestTrait;

/**
 * Tests the oEmbed URL resolver service.
 *
 * @coversDefaultClass \Drupal\media\OEmbed\UrlResolver
 *
 * @group media
 * @group #slow
 */
class UrlResolverTest extends MediaFunctionalTestBase {

  use OEmbedTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->lockHttpClientToFixtures();
    $this->useFixtureProviders();
  }

  /**
   * Data provider for testEndpointMatching().
   *
   * @see ::testEndpointMatching()
   *
   * @return array
   */
  public function providerEndpointMatching() {
    return [
      'match by endpoint: Twitter' => [
        'https://twitter.com/Dries/status/999985431595880448',
        'https://publish.twitter.com/oembed?url=https%3A//twitter.com/Dries/status/999985431595880448',
      ],
      'match by endpoint: Vimeo' => [
        'https://vimeo.com/14782834',
        'https://vimeo.com/api/oembed.json?url=https%3A//vimeo.com/14782834',
      ],
      'match by endpoint: Dailymotion' => [
        'https://www.dailymotion.com/video/x2vzluh',
        'https://www.dailymotion.com/services/oembed?url=https%3A//www.dailymotion.com/video/x2vzluh',
      ],
      'match by endpoint: Facebook' => [
        'https://www.facebook.com/facebook/videos/10153231379946729/',
        'https://www.facebook.com/plugins/video/oembed.json?url=https%3A//www.facebook.com/facebook/videos/10153231379946729/',
      ],
    ];
  }

  /**
   * Tests resource URL resolution with a matched provider endpoint.
   *
   * @covers ::getProviderByUrl
   * @covers ::getResourceUrl
   *
   * @param string $url
   *   The asset URL to resolve.
   * @param string $resource_url
   *   The expected oEmbed resource URL of the asset.
   *
   * @dataProvider providerEndpointMatching
   */
  public function testEndpointMatching($url, $resource_url) {
    $this->assertSame(
      $resource_url,
      $this->container->get('media.oembed.url_resolver')->getResourceUrl($url)
    );
  }

  /**
   * Tests that hook_oembed_resource_url_alter() is invoked.
   *
   * @depends testEndpointMatching
   */
  public function testResourceUrlAlterHook() {
    $this->container->get('module_installer')->install(['media_test_oembed']);

    $resource_url = $this->container->get('media.oembed.url_resolver')
      ->getResourceUrl('https://vimeo.com/14782834');

    $this->assertStringContainsString('altered=1', parse_url($resource_url, PHP_URL_QUERY));
  }

  /**
   * Data provider for testUrlDiscovery().
   *
   * @see ::testUrlDiscovery()
   *
   * @return array
   */
  public function providerUrlDiscovery() {
    return [
      'JSON resource' => [
        'video_vimeo.html',
        'https://vimeo.com/api/oembed.json?url=video_vimeo.html',
      ],
      'XML resource' => [
        'video_dailymotion.html',
        'https://www.dailymotion.com/services/oembed?url=video_dailymotion.html',
      ],
    ];
  }

  /**
   * Tests URL resolution when the URL is discovered by scanning the asset.
   *
   * @param string $url
   *   The asset URL to resolve.
   * @param string $resource_url
   *   The expected oEmbed resource URL of the asset.
   *
   * @covers ::discoverResourceUrl
   * @covers ::getProviderByUrl
   * @covers ::getResourceUrl
   *
   * @dataProvider providerUrlDiscovery
   */
  public function testUrlDiscovery($url, $resource_url) {
    $this->assertSame(
      $this->container->get('media.oembed.url_resolver')->getResourceUrl($url),
      $resource_url
    );
  }

}
