<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\media\OEmbed\Resource;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media\Plugin\media\Source\OEmbed;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;

/**
 * @coversDefaultClass \Drupal\media\Plugin\media\Source\OEmbed
 *
 * @group media
 */
class OEmbedSourceTest extends MediaKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['media'];

  /**
   * @covers ::getMetadata
   */
  public function testGetMetadata() {
    $configuration = [
      'source_field' => 'field_test_oembed',
    ];
    $plugin = OEmbed::create($this->container, $configuration, 'oembed', []);

    // Test that NULL is returned for a media item with no source value.
    $media = $this->prophesize('\Drupal\media\MediaInterface');
    $field_items = $this->prophesize('\Drupal\Core\Field\FieldItemListInterface');
    $field_items->isEmpty()->willReturn(TRUE);
    $media->get($configuration['source_field'])->willReturn($field_items->reveal());
    $this->assertNull($plugin->getMetadata($media->reveal(), 'type'));
  }

  /**
   * Data provider for ::testThumbnailUri().
   *
   * @return array
   *   Sets of arguments to pass to the test method.
   */
  public function providerThumbnailUri(): array {
    return [
      'no query string, file extension is known' => [
        'internal:/core/misc/druplicon.png',
      ],
      'with query string and file extension' => [
        'internal:/core/misc/druplicon.png?foo=bar',
      ],
      'no query string, unknown file extension' => [
        'internal:/core/misc/druplicon',
        [
          'Content-Type' => ['image/png'],
        ],
      ],
      'query string, unknown file extension' => [
        'internal:/core/misc/druplicon?pasta=ravioli',
        [
          'Content-Type' => ['image/png'],
        ],
      ],
    ];
  }

  /**
   * Tests that remote thumbnails are downloaded correctly.
   *
   * @param string $remote_thumbnail_url
   *   The URL of the remote thumbnail. This will be wired up to a mocked
   *   response containing the data from core/misc/druplicon.png.
   * @param array[] $thumbnail_headers
   *   (optional) If the thumbnail's file extension cannot be determined from
   *   its URL, a HEAD request will be made in an attempt to derive its
   *   extension from its Content-Type header. In this case, these are the
   *   headers that should be returned by the HEAD request. The keys are header
   *   names and the values are arrays of strings.
   *
   * @covers ::getLocalThumbnailUri
   *
   * @dataProvider providerThumbnailUri
   */
  public function testThumbnailUri(string $remote_thumbnail_url, array $thumbnail_headers = []): void {
    // Create a fake resource with the given thumbnail URL.
    $resource = Resource::rich('<html></html>', 16, 16, NULL, 'Test resource', NULL, NULL, NULL, $remote_thumbnail_url, 16, 16);
    $thumbnail_url = $resource->getThumbnailUrl()->toString();

    // There's no need to resolve the resource URL in this test; we just need
    // to fetch the resource.
    $this->container->set(
      'media.oembed.url_resolver',
      $this->prophesize(UrlResolverInterface::class)->reveal()
    );

    // Mock the resource fetcher so that it will return our fake resource.
    $resource_fetcher = $this->prophesize(ResourceFetcherInterface::class);
    $resource_fetcher->fetchResource(NULL)->willReturn($resource);
    $this->container->set('media.oembed.resource_fetcher', $resource_fetcher->reveal());

    // The source plugin will try to fetch the remote thumbnail, so mock the
    // HTTP client to ensure that request returns a response with some valid
    // image data.
    $data = Utils::tryFopen($this->getDrupalRoot() . '/core/misc/druplicon.png', 'r');
    $response = new Response(200, [], Utils::streamFor($data));
    $http_client = $this->prophesize(Client::class);
    // The thumbnail should only be downloaded once.
    $http_client->request('GET', $thumbnail_url)->willReturn($response)
      ->shouldBeCalledOnce();

    // If the file extension cannot be derived from the URL, a HEAD request
    // should be made.
    if ($thumbnail_headers) {
      $response = new Response(200, $thumbnail_headers);
      $http_client->request('HEAD', $thumbnail_url)->willReturn($response);
    }
    else {
      $http_client->request('HEAD', $thumbnail_url)->shouldNotBeCalled();
    }
    $this->container->set('http_client', $http_client->reveal());

    $media_type = $this->createMediaType('oembed:video');
    $source = $media_type->getSource();

    $media = Media::create([
      'bundle' => $media_type->id(),
      $source->getSourceFieldDefinition($media_type)->getName() => $this->randomString(),
    ]);
    $media->save();

    // Get the local thumbnail URI and ensure that it does not contain any
    // query string.
    $expected_uri = 'public://oembed_thumbnails/' . Crypt::hashBase64($thumbnail_url) . '.png';
    $this->assertSame($expected_uri, $source->getMetadata($media, 'thumbnail_uri'));
    // Ensure that the thumbnail is only downloaded once.
    $source->getMetadata($media, 'thumbnail_uri');
  }

}
