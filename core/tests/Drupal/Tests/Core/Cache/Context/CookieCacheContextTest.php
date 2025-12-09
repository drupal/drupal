<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache\Context;

use Drupal\Core\Cache\Context\CookiesCacheContext;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests Drupal\Core\Cache\Context\CookiesCacheContext.
 */
#[CoversClass(CookiesCacheContext::class)]
#[Group('Cache')]
class CookieCacheContextTest extends UnitTestCase {

  /**
   * Tests get context.
   *
   * @legacy-covers ::getContext
   */
  #[DataProvider('providerTestGetContext')]
  public function testGetContext($cookies, $cookie_name, $context): void {
    $request_stack = new RequestStack();
    $request = Request::create('/', 'GET');
    foreach ($cookies as $cookie => $value) {
      $request->cookies->set($cookie, $value);
    }
    $request_stack->push($request);
    $cache_context = new CookiesCacheContext($request_stack);
    $this->assertSame($cache_context->getContext($cookie_name), $context);
  }

  /**
   * Provides a list of cookies and expected cache contexts.
   */
  public static function providerTestGetContext(): array {
    return [
      [['foo' => 1, 'bar' => 2, 'baz' => 3], 'foo', 1],
      // Context is ordered by cookie name.
      [['foo' => 1, 'bar' => 2, 'baz' => 3], NULL, 'bar=2&baz=3&foo=1'],
    ];
  }

}
