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
   * @covers ::getLocalThumbnailUri
   */
  public function testLocalThumbnailUriQueryStringIsIgnored() {
    // There's no need to resolve the resource URL in this test; we just need
    // to fetch the resource.
    $this->container->set(
      'media.oembed.url_resolver',
      $this->prophesize(UrlResolverInterface::class)->reveal()
    );

    $thumbnail_url = Url::fromUri('internal:/core/misc/druplicon.png?foo=bar');

    // Create a mocked resource whose thumbnail URL contains a query string.
    $resource = $this->prophesize(Resource::class);
    $resource->getTitle()->willReturn('Test resource');
    $resource->getThumbnailUrl()->willReturn($thumbnail_url);

    // The source plugin will try to fetch the remote thumbnail, so mock the
    // HTTP client to ensure that request returns an empty "OK" response.
    $http_client = $this->prophesize(Client::class);
    $http_client->get(Argument::type('string'))->willReturn(new Response());
    $this->container->set('http_client', $http_client->reveal());

    // Mock the resource fetcher so that it will return our mocked resource.
    $resource_fetcher = $this->prophesize(ResourceFetcherInterface::class);
    $resource_fetcher->fetchResource(NULL)->willReturn($resource->reveal());
    $this->container->set('media.oembed.resource_fetcher', $resource_fetcher->reveal());

    $media_type = $this->createMediaType('oembed:video');
    $source = $media_type->getSource();

    $media = Media::create([
      'bundle' => $media_type->id(),
      $source->getSourceFieldDefinition($media_type)->getName() => $this->randomString(),
    ]);
    $media->save();

    // Get the local thumbnail URI and ensure that it does not contain any
    // query string.
    $local_thumbnail_uri = $media_type->getSource()->getMetadata($media, 'thumbnail_uri');
    $expected_uri = 'public://oembed_thumbnails/' . Crypt::hashBase64('/core/misc/druplicon.png') . '.png';
    $this->assertSame($expected_uri, $local_thumbnail_uri);
  }

}
