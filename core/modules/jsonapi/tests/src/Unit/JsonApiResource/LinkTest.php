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
   * @covers ::merge
   * @dataProvider mergeTargetAttributesProvider
   */
  public function testMergeTargetAttributes($a, $b, $expected) {
    $this->assertSame($expected->getTargetAttributes(), Link::merge($a, $b)->getTargetAttributes());
  }

  /**
   * Provides test data for link merging.
   */
  public function mergeTargetAttributesProvider() {
    $cases = [
      'strings' => [
        ['key' => 'foo'],
        ['key' => 'bar'],
        ['key' => ['foo', 'bar']],
      ],
      'string and array' => [
        ['key' => 'foo'],
        ['key' => ['bar', 'baz']],
        ['key' => ['foo', 'bar', 'baz']],
      ],
      'one-dimensional indexed arrays' => [
        ['key' => ['foo']],
        ['key' => ['bar']],
        ['key' => ['foo', 'bar']],
      ],
      'one-dimensional keyed arrays' => [
        ['key' => ['foo' => 'tball']],
        ['key' => ['bar' => 'ista']],
        [
          'key' => [
            'foo' => 'tball',
            'bar' => 'ista',
          ],
        ],
      ],
      'two-dimensional indexed arrays' => [
        ['one' => ['two' => ['foo']]],
        ['one' => ['two' => ['bar']]],
        ['one' => ['two' => ['foo', 'bar']]],
      ],
      'two-dimensional keyed arrays' => [
        ['one' => ['two' => ['foo' => 'tball']]],
        ['one' => ['two' => ['bar' => 'ista']]],
        [
          'one' => [
            'two' => [
              'foo' => 'tball',
              'bar' => 'ista',
            ],
          ],
        ],
      ],
    ];
    $this->mockUrlAssembler();
    return array_map(function ($arguments) {
      return array_map(function ($attributes) {
        return new Link(new CacheableMetadata(), Url::fromUri('https://jsonapi.org/'), ['item'], $attributes);
      }, $arguments);
    }, $cases);
  }

  /**
   * Mocks the unrouted URL assembler.
   */
  protected function mockUrlAssembler() {
    $url_assembler = $this->getMockBuilder(UnroutedUrlAssemblerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $url_assembler->method('assemble')->willReturn((new GeneratedUrl())->setGeneratedUrl('https://jsonapi.org/'));

    $container = new ContainerBuilder();
    $container->set('unrouted_url_assembler', $url_assembler);
    \Drupal::setContainer($container);
  }

}
