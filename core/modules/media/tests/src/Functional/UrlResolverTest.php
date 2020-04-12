<?php

namespace Drupal\Tests\media\Functional;

use Drupal\Tests\media\Traits\OEmbedTestTrait;

/**
 * Tests the oEmbed URL resolver service.
 *
 * @coversDefaultClass \Drupal\media\OEmbed\UrlResolver
 *
 * @group media
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
  protected function setUp() {
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
        'https://publish.twitter.com/oembed?url=https://twitter.com/Dries/status/999985431595880448',
      ],
      'match by endpoint: Vimeo' => [
        'https://vimeo.com/14782834',
        'https://vimeo.com/api/oembed.json?url=https://vimeo.com/14782834',
      ],
      'match by endpoint: CollegeHumor' => [
        'http://www.collegehumor.com/video/40002870/lets-not-get-a-drink-sometime',
        'http://www.collegehumor.com/oembed.json?url=http://www.collegehumor.com/video/40002870/lets-not-get-a-drink-sometime',
      ],
    ];
  }

  /**
   * Tests resource URL resolution when the asset URL can be matched to a
   * provider endpoint.
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
        'video_collegehumor.html',
        // The endpoint does not explicitly declare that it supports XML, so
        // only JSON support is assumed, which is why the discovered URL
        // contains '.json'. However, the fetched HTML file contains a
        // relationship to an XML representation of the resource, with the
        // application/xml+oembed MIME type.
        'http://www.collegehumor.com/oembed.json?url=video_collegehumor.html',
      ],
    ];
  }

  /**
   * Tests URL resolution when the resource URL must be actively discovered by
   * scanning the asset.
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
