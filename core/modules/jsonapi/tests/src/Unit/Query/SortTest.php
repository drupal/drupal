<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Unit\Query;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\Container;
use Drupal\jsonapi\Query\Sort;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @coversDefaultClass \Drupal\jsonapi\Query\Sort
 * @group jsonapi
 *
 * @internal
 */
class SortTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new Container();
    $cache_context_manager = $this->prophesize(CacheContextsManager::class);
    $cache_context_manager->assertValidTokens(Argument::any())
      ->willReturn(TRUE);
    $container->set('cache_contexts_manager', $cache_context_manager->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::createFromQueryParameter
   * @dataProvider parameterProvider
   */
  public function testCreateFromQueryParameter($input, $expected): void {
    $sort = Sort::createFromQueryParameter($input);
    foreach ($sort->fields() as $index => $sort_field) {
      $this->assertEquals($expected[$index]['path'], $sort_field['path']);
      $this->assertEquals($expected[$index]['direction'], $sort_field['direction']);
      $this->assertEquals($expected[$index]['langcode'], $sort_field['langcode']);
    }
  }

  /**
   * Provides a suite of shortcut sort parameters and their expected expansions.
   */
  public static function parameterProvider() {
    return [
      ['lorem', [['path' => 'lorem', 'direction' => 'ASC', 'langcode' => NULL]]],
      [
        '-lorem',
        [['path' => 'lorem', 'direction' => 'DESC', 'langcode' => NULL]],
      ],
      ['-lorem,ipsum', [
        ['path' => 'lorem', 'direction' => 'DESC', 'langcode' => NULL],
        ['path' => 'ipsum', 'direction' => 'ASC', 'langcode' => NULL],
      ],
      ],
      ['-lorem,-ipsum', [
        ['path' => 'lorem', 'direction' => 'DESC', 'langcode' => NULL],
        ['path' => 'ipsum', 'direction' => 'DESC', 'langcode' => NULL],
      ],
      ],
      [[
        ['path' => 'lorem', 'langcode' => NULL],
        ['path' => 'ipsum', 'langcode' => 'ca'],
        ['path' => 'dolor', 'direction' => 'ASC', 'langcode' => 'ca'],
        ['path' => 'sit', 'direction' => 'DESC', 'langcode' => 'ca'],
      ], [
        ['path' => 'lorem', 'direction' => 'ASC', 'langcode' => NULL],
        ['path' => 'ipsum', 'direction' => 'ASC', 'langcode' => 'ca'],
        ['path' => 'dolor', 'direction' => 'ASC', 'langcode' => 'ca'],
        ['path' => 'sit', 'direction' => 'DESC', 'langcode' => 'ca'],
      ],
      ],
    ];
  }

  /**
   * @covers ::createFromQueryParameter
   * @dataProvider badParameterProvider
   */
  public function testCreateFromQueryParameterFail($input): void {
    $this->expectException(BadRequestHttpException::class);
    Sort::createFromQueryParameter($input);
  }

  /**
   * Data provider for testCreateFromQueryParameterFail.
   */
  public static function badParameterProvider() {
    return [
      [[['lorem']]],
      [''],
    ];
  }

}
