<?php

namespace Drupal\Tests\jsonapi\Unit\Query;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\Container;
use Drupal\jsonapi\Query\OffsetPage;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @coversDefaultClass \Drupal\jsonapi\Query\OffsetPage
 * @group jsonapi
 *
 * @internal
 */
class OffsetPageTest extends UnitTestCase {

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
  public function testCreateFromQueryParameter($original, $expected) {
    $actual = OffsetPage::createFromQueryParameter($original);
    $this->assertEquals($expected['offset'], $actual->getOffset());
    $this->assertEquals($expected['limit'], $actual->getSize());
  }

  /**
   * Data provider for testCreateFromQueryParameter.
   */
  public function parameterProvider() {
    return [
      [['offset' => 12, 'limit' => 20], ['offset' => 12, 'limit' => 20]],
      [['offset' => 12, 'limit' => 60], ['offset' => 12, 'limit' => 50]],
      [['offset' => 12], ['offset' => 12, 'limit' => 50]],
      [['offset' => 0], ['offset' => 0, 'limit' => 50]],
      [[], ['offset' => 0, 'limit' => 50]],
    ];
  }

  /**
   * @covers ::createFromQueryParameter
   */
  public function testCreateFromQueryParameterFail() {
    $this->expectException(BadRequestHttpException::class);
    OffsetPage::createFromQueryParameter('lorem');
  }

}
