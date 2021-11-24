<?php

namespace Drupal\Tests\Core\Cache\Context;

use Drupal\Core\Cache\Context\CookiesCacheContext;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\Core\Cache\Context\CookiesCacheContext
 * @group Cache
 */
class CookieCacheContextTest extends UnitTestCase {

  /**
   * @covers ::getContext
   *
   * @dataProvider providerTestGetContext
   */
  public function testGetContext($cookies, $cookie_name, $context) {
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
  public function providerTestGetContext() {
    return [
      [['foo' => 1, 'bar' => 2, 'baz' => 3], 'foo', 1],
      // Context is ordered by cookie name.
      [['foo' => 1, 'bar' => 2, 'baz' => 3], NULL, 'bar=2&baz=3&foo=1'],
    ];
  }

}
