<?php

namespace Drupal\Tests\jsonapi\Unit\JsonApiResource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Url;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\jsonapi\JsonApiResource\Link;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\jsonapi\JsonApiResource\Link
 * @group jsonapi
 *
 * @internal
 */
class LinkTest extends UnitTestCase {

  /**
   * @covers ::compare
   * @dataProvider linkComparisonProvider
   */
  public function testLinkComparison(Link $a, Link $b, $expected) {
    $actual = Link::compare($a, $b);
    $this->assertSame($expected, $actual === 0);
  }

  /**
   * Provides test data for link comparison.
   */
  public function linkComparisonProvider() {
    $this->mockUrlAssembler();
    return [
      'same href and same link relation type' => [
        new Link(new CacheableMetadata(), Url::fromUri('https://jsonapi.org/foo'), 'self'),
        new Link(new CacheableMetadata(), Url::fromUri('https://jsonapi.org/foo'), 'self'),
        TRUE,
      ],
      'different href and same link relation type' => [
        new Link(new CacheableMetadata(), Url::fromUri('https://jsonapi.org/foo'), 'self'),
        new Link(new CacheableMetadata(), Url::fromUri('https://jsonapi.org/bar'), 'self'),
        FALSE,
      ],
      'same href and different link relation type' => [
        new Link(new CacheableMetadata(), Url::fromUri('https://jsonapi.org/foo'), 'self'),
        new Link(new CacheableMetadata(), Url::fromUri('https://jsonapi.org/foo'), 'related'),
        FALSE,
      ],
      'same href and same link relation type and empty target attributes' => [
        new Link(new CacheableMetadata(), Url::fromUri('https://jsonapi.org/foo'), 'self', []),
        new Link(new CacheableMetadata(), Url::fromUri('https://jsonapi.org/foo'), 'self', []),
        TRUE,
      ],
      'same href and same link relation type and same target attributes' => [
        new Link(new CacheableMetadata(), Url::fromUri('https://jsonapi.org/foo'), 'self', ['anchor' => 'https://jsonapi.org']),
        new Link(new CacheableMetadata(), Url::fromUri('https://jsonapi.org/foo'), 'self', ['anchor' => 'https://jsonapi.org']),
        TRUE,
      ],
      // These links are not considered equivalent because it would while the
      // `href` remains the same, the anchor changes the context of the link.
      'same href and same link relation type and different target attributes' => [
        new Link(new CacheableMetadata(), Url::fromUri('https://jsonapi.org/boy'), 'self', ['title' => 'sue']),
        new Link(new CacheableMetadata(), Url::fromUri('https://jsonapi.org/boy'), 'self', ['anchor' => '/sob', 'title' => 'pa']),
        FALSE,
      ],
      'same href and same link relation type and same nested target attributes' => [
        new Link(new CacheableMetadata(), Url::fromUri('https://jsonapi.org/foo'), 'self', ['data' => ['foo' => 'bar']]),
        new Link(new cacheablemetadata(), Url::fromUri('https://jsonapi.org/foo'), 'self', ['data' => ['foo' => 'bar']]),
        TRUE,
      ],
      'same href and same link relation type and different nested target attributes' => [
        new Link(new CacheableMetadata(), Url::fromUri('https://jsonapi.org/foo'), 'self', ['data' => ['foo' => 'bar']]),
        new Link(new CacheableMetadata(), Url::fromUri('https://jsonapi.org/foo'), 'self', ['data' => ['foo' => 'baz']]),
        FALSE,
      ],
      // These links are not considered equivalent because it would be unclear
      // which title corresponds to which link relation type.
      'same href and different link relation types and different target attributes' => [
        new Link(new CacheableMetadata(), Url::fromUri('https://jsonapi.org/boy'), 'self', ['title' => 'A boy named Sue']),
        new Link(new CacheableMetadata(), Url::fromUri('https://jsonapi.org/boy'), 'edit', ['title' => 'Change name to Bill or George']),
        FALSE,
      ],
    ];
  }

  /**
   * @covers ::merge
   * @dataProvider linkMergeProvider
   */
  public function testLinkMerge(Link $a, Link $b, $expected) {
    if ($expected instanceof Link) {
      $this->assertSame($expected->getCacheTags(), Link::merge($a, $b)->getCacheTags());
    }
    else {
      $this->expectExceptionObject($expected);
      Link::merge($a, $b);
    }
  }

  /**
   * Provides test data for link merging.
   */
  public function linkMergeProvider() {
    $this->mockUrlAssembler();
    return [
      'same everything' => [
        new Link((new CacheableMetadata())->addCacheTags(['foo']), Url::fromUri('https://jsonapi.org/foo'), 'self'),
        new Link((new CacheableMetadata())->addCacheTags(['foo']), Url::fromUri('https://jsonapi.org/foo'), 'self'),
        new Link((new CacheableMetadata())->addCacheTags(['foo']), Url::fromUri('https://jsonapi.org/foo'), 'self'),
      ],
      'different cache tags' => [
        new Link((new CacheableMetadata())->addCacheTags(['foo']), Url::fromUri('https://jsonapi.org/foo'), 'self'),
        new Link((new CacheableMetadata())->addCacheTags(['bar']), Url::fromUri('https://jsonapi.org/foo'), 'self'),
        new Link((new CacheableMetadata())->addCacheTags(['foo', 'bar']), Url::fromUri('https://jsonapi.org/foo'), 'self'),
      ],
    ];
  }

  /**
   * @covers ::getLinkRelationType
   */
  public function testGetLinkRelationType() {
    $this->mockUrlAssembler();
    $link = new Link((new CacheableMetadata())->addCacheTags(['foo']), Url::fromUri('https://jsonapi.org/foo'), 'self');
    $this->assertSame('self', $link->getLinkRelationType());
  }

  /**
   * Mocks the unrouted URL assembler.
   */
  protected function mockUrlAssembler() {
    $url_assembler = $this->getMockBuilder(UnroutedUrlAssemblerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $url_assembler->method('assemble')->willReturnCallback(function ($uri) {
      return (new GeneratedUrl())->setGeneratedUrl($uri);
    });

    $container = new ContainerBuilder();
    $container->set('unrouted_url_assembler', $url_assembler);
    \Drupal::setContainer($container);
  }

}
