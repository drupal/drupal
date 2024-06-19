<?php

declare(strict_types=1);

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
  public function testLinkComparison(array $a, array $b, bool $expected): void {
    $this->mockUrlAssembler();

    $link_a = new Link(new CacheableMetadata(), Url::fromUri($a[0]), $a[1], $a[2] ?? []);
    $link_b = new Link(new CacheableMetadata(), Url::fromUri($b[0]), $b[1], $b[2] ?? []);

    $actual = Link::compare($link_a, $link_b);
    $this->assertSame($expected, $actual === 0);
  }

  /**
   * Provides test data for link comparison.
   */
  public static function linkComparisonProvider(): \Generator {
    yield 'same href and same link relation type' => [
      ['https://jsonapi.org/foo', 'self'],
      ['https://jsonapi.org/foo', 'self'],
      TRUE,
    ];
    yield 'different href and same link relation type' => [
      ['https://jsonapi.org/foo', 'self'],
      ['https://jsonapi.org/bar', 'self'],
      FALSE,
    ];
    yield 'same href and different link relation type' => [
      ['https://jsonapi.org/foo', 'self'],
      ['https://jsonapi.org/foo', 'related'],
      FALSE,
    ];
    yield 'same href and same link relation type and empty target attributes' => [
      ['https://jsonapi.org/foo', 'self', []],
      ['https://jsonapi.org/foo', 'self', []],
      TRUE,
    ];
    yield 'same href and same link relation type and same target attributes' => [
      ['https://jsonapi.org/foo', 'self', ['anchor' => 'https://jsonapi.org']],
      ['https://jsonapi.org/foo', 'self', ['anchor' => 'https://jsonapi.org']],
      TRUE,
    ];
    // These links are not considered equivalent because it would while the
    // `href` remains the same, the anchor changes the context of the link.
    yield 'same href and same link relation type and different target attributes' => [
      ['https://jsonapi.org/boy', 'self', ['title' => 'sue']],
      ['https://jsonapi.org/boy', 'self', ['anchor' => '/sob', 'title' => 'pa']],
      FALSE,
    ];
    yield 'same href and same link relation type and same nested target attributes' => [
      ['https://jsonapi.org/foo', 'self', ['data' => ['foo' => 'bar']]],
      ['https://jsonapi.org/foo', 'self', ['data' => ['foo' => 'bar']]],
      TRUE,
    ];
    yield 'same href and same link relation type and different nested target attributes' => [
      ['https://jsonapi.org/foo', 'self', ['data' => ['foo' => 'bar']]],
      ['https://jsonapi.org/foo', 'self', ['data' => ['foo' => 'baz']]],
      FALSE,
    ];
    // These links are not considered equivalent because it would be unclear
    // which title corresponds to which link relation type.
    yield 'same href and different link relation types and different target attributes' => [
      ['https://jsonapi.org/boy', 'self', ['title' => 'A boy named Sue']],
      ['https://jsonapi.org/boy', 'edit', ['title' => 'Change name to Bill or George']],
      FALSE,
    ];
  }

  /**
   * @covers ::merge
   * @dataProvider linkMergeProvider
   */
  public function testLinkMerge(array $a, array $b, array $expected): void {
    $this->mockUrlAssembler();

    $link_a = new Link((new CacheableMetadata())->addCacheTags($a[0]), Url::fromUri($a[1]), $a[2]);
    $link_b = new Link((new CacheableMetadata())->addCacheTags($b[0]), Url::fromUri($b[1]), $b[2]);
    $link_expected = new Link((new CacheableMetadata())->addCacheTags($expected[0]), Url::fromUri($expected[1]), $expected[2]);

    $this->assertSame($link_expected->getCacheTags(), Link::merge($link_a, $link_b)->getCacheTags());
  }

  /**
   * Provides test data for link merging.
   */
  public static function linkMergeProvider(): \Generator {
    yield 'same everything' => [
      [['foo'], 'https://jsonapi.org/foo', 'self'],
      [['foo'], 'https://jsonapi.org/foo', 'self'],
      [['foo'], 'https://jsonapi.org/foo', 'self'],
    ];
    yield 'different cache tags' => [
      [['foo'], 'https://jsonapi.org/foo', 'self'],
      [['bar'], 'https://jsonapi.org/foo', 'self'],
      [['foo', 'bar'], 'https://jsonapi.org/foo', 'self'],
    ];
  }

  /**
   * @covers ::getLinkRelationType
   */
  public function testGetLinkRelationType(): void {
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
