<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\Component\Utility\Crypt;
use Drupal\media\Entity\Media;
use Drupal\media\OEmbed\Resource;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media\Plugin\media\Source\OEmbed;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
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
      'no query string, extension in URL' => [
        'internal:/core/misc/druplicon.png',
        [],
        'png',
      ],
      'with query string, extension in URL' => [
        'internal:/core/misc/druplicon.png?foo=bar',
        [],
        'png',
      ],
      'no query string or extension in URL, has MIME type' => [
        'internal:/core/misc/druplicon',
        [
          'Content-Type' => ['image/png'],
        ],
        'png',
      ],
      'query string but no extension in URL, has MIME type' => [
        'internal:/core/misc/druplicon?pasta=ravioli',
        [
          'Content-Type' => ['image/png'],
        ],
        'png',
      ],
      'no query string, MIME type, or extension in URL' => [
        'internal:/core/misc/druplicon',
        [],
        '',
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
   *   If the thumbnail's file extension cannot be determined from its URL, an
   *   attempt will be made to derive the extension from the response's
   *   Content-Type header. This array contains the headers that should be
   *   returned with the thumbnail response, where the keys are header names and
   *   the values are arrays of strings.
   * @param string $expected_extension
   *   The extension that the downloaded thumbnail should have.
   *
   * @covers ::getLocalThumbnailUri
   *
   * @dataProvider providerThumbnailUri
   */
  public function testThumbnailUri(string $remote_thumbnail_url, array $thumbnail_headers, string $expected_extension): void {
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
    $response = new Response(200, $thumbnail_headers, Utils::streamFor($data));
    $handler = new MockHandler([$response]);
    $client = new Client([
      'handler' => new HandlerStack($handler),
    ]);
    $this->container->set('http_client', $client);

    $media_type = $this->createMediaType('oembed:video');
    $source = $media_type->getSource();

    // Add some HTML to the global site slogan, and use the site:slogan token in
    // the thumbnail path, in order to prove that the final thumbnail path is
    // stripped of HTML tags, and XML entities are decoded.
    $this->config('system.site')
      ->set('slogan', '<h1>this&amp;that</h1>')
      ->save();
    $configuration = $source->getConfiguration();
    $configuration['thumbnails_directory'] .= '/[site:slogan]';
    $source->setConfiguration($configuration);
    $media_type->save();

    $media = Media::create([
      'bundle' => $media_type->id(),
      $source->getSourceFieldDefinition($media_type)->getName() => $this->randomString(),
    ]);
    $media->save();

    // The thumbnail directory should include the current date, as per the
    // default configuration of the oEmbed source plugin.
    $date = date('Y-m', $this->container->get('datetime.time')->getRequestTime());

    // The thumbnail should have a file extension, even if it wasn't in the URL.
    $expected_uri = "public://oembed_thumbnails/$date/this&that/" . Crypt::hashBase64($thumbnail_url) . ".$expected_extension";
    $this->assertSame($expected_uri, $source->getMetadata($media, 'thumbnail_uri'));

    // Even if we get the thumbnail_uri more than once, it should only be
    // downloaded once. The HTTP client will throw an exception if we try to
    // do another request without setting up another response.
    $source->getMetadata($media, 'thumbnail_uri');

    // The downloaded thumbnail should be usable by the image toolkit.
    $this->assertFileExists($expected_uri);
    /** @var \Drupal\Core\Image\Image $image */
    $image = $this->container->get('image.factory')->get($expected_uri);
    $this->assertTrue($image->isValid());
  }

}
