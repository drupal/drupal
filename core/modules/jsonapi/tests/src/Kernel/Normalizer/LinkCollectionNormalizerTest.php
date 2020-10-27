<?php

namespace Drupal\Tests\jsonapi\Kernel\Normalizer;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;
use Drupal\jsonapi\JsonApiResource\Link;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\Normalizer\LinkCollectionNormalizer;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\jsonapi\Normalizer\LinkCollectionNormalizer
 * @group jsonapi
 *
 * @internal
 */
class LinkCollectionNormalizerTest extends KernelTestBase {

  /**
   * The subject under test.
   *
   * @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface
   */
  protected $normalizer;

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'jsonapi',
    'serialization',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->normalizer = new LinkCollectionNormalizer();
    $this->normalizer->setSerializer($this->container->get('jsonapi.serializer'));
  }

  /**
   * Tests the link collection normalizer.
   */
  public function testNormalize() {
    $link_context = new ResourceObject(new CacheableMetadata(), new ResourceType('n/a', 'n/a', 'n/a'), 'n/a', NULL, [], new LinkCollection([]));
    $link_collection = (new LinkCollection([]))
      ->withLink('related', new Link(new CacheableMetadata(), Url::fromUri('http://example.com/post/42'), 'related', ['title' => 'Most viewed']))
      ->withLink('related', new Link(new CacheableMetadata(), Url::fromUri('http://example.com/post/42'), 'related', ['title' => 'Top rated']))
      ->withContext($link_context);
    $normalized = $this->normalizer->normalize($link_collection)->getNormalization();
    $this->assertIsArray($normalized);
    foreach (array_keys($normalized) as $key) {
      $this->assertStringStartsWith('related', $key);
    }
    $this->assertSame([
      [
        'href' => 'http://example.com/post/42',
        'meta' => [
          'title' => 'Most viewed',
        ],
      ],
      [
        'href' => 'http://example.com/post/42',
        'meta' => [
          'title' => 'Top rated',
        ],
      ],
    ], array_values($normalized));
  }

}
