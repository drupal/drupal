<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache\Context;

use Drupal\Core\Cache\Context\PathParentCacheContext;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\Core\Cache\Context\PathParentCacheContext
 * @group Cache
 */
class PathParentCacheContextTest extends UnitTestCase {

  /**
   * @covers ::getContext
   *
   * @dataProvider providerTestGetContext
   */
  public function testGetContext($original_path, $context) {
    $request_stack = new RequestStack();
    $request = Request::create($original_path);
    $request_stack->push($request);
    $cache_context = new PathParentCacheContext($request_stack);
    $this->assertSame($cache_context->getContext(), $context);
  }

  /**
   * Provides a list of paths and expected cache contexts.
   */
  public function providerTestGetContext() {
    return [
      ['/some/path', 'some'],
      ['/some/other-path', 'some'],
      ['/some/other/path', 'some/other'],
      ['/some/other/path?q=foo&b=bar', 'some/other'],
      ['/some', ''],
      ['/', ''],
    ];
  }

}
