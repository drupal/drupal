<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache\Context;

use Drupal\Core\Cache\Context\IsFrontPathCacheContext;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \Drupal\Core\Cache\Context\IsFrontPathCacheContext
 * @group Cache
 */
class IsFrontPathCacheContextTest extends UnitTestCase {

  /**
   * @covers ::getContext
   */
  public function testGetContextFront(): void {
    $cache_context = new IsFrontPathCacheContext($this->createPathMatcher(TRUE)->reveal());
    $this->assertSame('is_front.1', $cache_context->getContext());
  }

  /**
   * @covers ::getContext
   */
  public function testGetContextNotFront(): void {
    $cache_context = new IsFrontPathCacheContext($this->createPathMatcher(FALSE)->reveal());
    $this->assertSame('is_front.0', $cache_context->getContext());
  }

  /**
   * Creates a PathMatcherInterface prophecy.
   *
   * @param bool $is_front
   *   Whether the page is the front page.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Path\PathMatcherInterface>
   *   The path matcher prophecy.
   */
  protected function createPathMatcher($is_front): ObjectProphecy {
    $path_matcher = $this->prophesize(PathMatcherInterface::class);
    $path_matcher->isFrontPage()
      ->willReturn($is_front);

    return $path_matcher;
  }

}
