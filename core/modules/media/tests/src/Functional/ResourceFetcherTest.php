<?php

namespace Drupal\Tests\media\Functional;

use Drupal\media\OEmbed\Resource;
use Drupal\Tests\media\Traits\OEmbedTestTrait;

/**
 * Tests the oEmbed resource fetcher service.
 *
 * @coversDefaultClass \Drupal\media\OEmbed\ResourceFetcher
 *
 * @group media
 */
class ResourceFetcherTest extends MediaFunctionalTestBase {

  use OEmbedTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->useFixtureProviders();
    $this->lockHttpClientToFixtures();
  }

  /**
   * Data provider for testFetchResource().
   *
   * @return array
   */
  public function providerFetchResource() {
    return [
      'JSON resource' => [
        'video_vimeo.json',
        'Vimeo',
        'Drupal Rap Video - Schipulcon09',
      ],
      'XML resource' => [
        'video_collegehumor.xml',
        'CollegeHumor',
        "Let's Not Get a Drink Sometime",
      ],
    ];
  }

  /**
   * Tests resource fetching.
   *
   * @param string $resource_url
   *   The URL of the resource to fetch, relative to the base URL.
   * @param string $provider_name
   *   The expected name of the resource provider.
   * @param string $title
   *   The expected title of the resource.
   *
   * @covers ::fetchResource
   *
   * @dataProvider providerFetchResource
   */
  public function testFetchResource($resource_url, $provider_name, $title) {
    /** @var \Drupal\media\OEmbed\Resource $resource */
    $resource = $this->container->get('media.oembed.resource_fetcher')
      ->fetchResource($resource_url);

    $this->assertInstanceOf(Resource::class, $resource);
    $this->assertSame($provider_name, $resource->getProvider()->getName());
    $this->assertSame($title, $resource->getTitle());
  }

}
