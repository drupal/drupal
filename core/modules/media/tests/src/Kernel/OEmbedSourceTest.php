<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\media\Entity\Media;
use Drupal\media\OEmbed\Resource;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media\Plugin\media\Source\OEmbed;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Prophecy\Argument;

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
      'no query string, unknown file extension, exception' => [
        'internal:/core/misc/druplicon',
        '\GuzzleHttp\Exception\TransferException',
      ],
    ];
  }

  /**
   * Tests that remote thumbnails are downloaded correctly.
   *
   * @param string $remote_thumbnail_url
   *   The URL of the remote thumbnail. This will be wired up to a mocked
   *   response containing the data from core/misc/druplicon.png.
   * @param array[]|string $thumbnail_headers
   *   (optional) If the thumbnail's file extension cannot be determined from
   *   its URL, a HEAD request will be made in an attempt to derive its
   *   extension from its Content-Type header. If this is an array, it contains
   *   headers that should be returned by the HEAD request, where the keys are
   *   header names and the values are arrays of strings. If it's the name of a
   *   throwable class, it is the exception class that should be thrown by the
   *   HTTP client.
   *
   * @covers ::getLocalThumbnailUri
   *
   * @dataProvider providerThumbnailUri
   */
  public function testThumbnailUri(string $remote_thumbnail_url, $thumbnail_headers = NULL): void {
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
    $resource_fetcher->fetchResource(Argument::any())
      ->willReturn($resource);
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
    // The extension we expect the downloaded thumbnail to have.
    $expected_extension = 'png';

    // If the file extension cannot be derived from the URL, a single HEAD
    // request should be made to try and determine its type from the
    // Content-Type HTTP header.
    if (is_array($thumbnail_headers)) {
      $response = new Response(200, $thumbnail_headers);
      $http_client->request('HEAD', $thumbnail_url)
        ->willReturn($response)
        ->shouldBeCalledOnce();
    }
    elseif (is_a($thumbnail_headers, 'Throwable', TRUE)) {
      $e = new $thumbnail_headers('Nope!');

      $http_client->request('HEAD', $thumbnail_url)
        ->willThrow($e)
        ->shouldBeCalledOnce();

      // Ensure that the exception is logged.
      $logger = $this->prophesize('\Psr\Log\LoggerInterface');
      $logger->log(RfcLogLevel::WARNING, $e->getMessage(), Argument::cetera())
        ->shouldBeCalled();
      $this->container->get('logger.factory')->addLogger($logger->reveal());

      // If the request fails, we won't be able to determine the thumbnail's
      // extension.
      $expected_extension = '';
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

    // The thumbnail should have a file extension, even if it wasn't in the URL.
    $expected_uri = 'public://oembed_thumbnails/' . Crypt::hashBase64($thumbnail_url) . ".$expected_extension";
    $this->assertSame($expected_uri, $source->getMetadata($media, 'thumbnail_uri'));
    // Even if we get the thumbnail_uri more than once, it should only be
    // downloaded once (this is verified by the shouldBeCalledOnce() checks
    // in the mocked HTTP client).
    $source->getMetadata($media, 'thumbnail_uri');
    // The downloaded thumbnail should be usable by the image toolkit.
    $this->assertFileExists($expected_uri);
    /** @var \Drupal\Core\Image\Image $image */
    $image = $this->container->get('image.factory')->get($expected_uri);
    $this->assertTrue($image->isValid());
  }

}
