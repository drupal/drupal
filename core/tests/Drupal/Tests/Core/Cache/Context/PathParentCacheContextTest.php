<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache\Context;

use Drupal\Core\Cache\Context\PathParentCacheContext;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests Drupal\Core\Cache\Context\PathParentCacheContext.
 */
#[CoversClass(PathParentCacheContext::class)]
#[Group('Cache')]
class PathParentCacheContextTest extends UnitTestCase {

  /**
   * Tests get context.
   */
  #[DataProvider('providerTestGetContext')]
  public function testGetContext($original_path, $context): void {
    $request_stack = new RequestStack();
    $request = Request::create($original_path);
    $request_stack->push($request);
    $cache_context = new PathParentCacheContext($request_stack);
    $this->assertSame($cache_context->getContext(), $context);
  }

  /**
   * Provides a list of paths and expected cache contexts.
   */
  public static function providerTestGetContext(): array {
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
