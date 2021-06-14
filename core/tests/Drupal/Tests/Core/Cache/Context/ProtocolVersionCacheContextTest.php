<?php

namespace Drupal\Tests\Core\Cache\Context;

use Drupal\Core\Cache\Context\ProtocolVersionCacheContext;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\Core\Cache\Context\ProtocolVersionCacheContext
 * @group Cache
 */
class ProtocolVersionCacheContextTest extends UnitTestCase {

  /**
   * @covers ::getContext
   *
   * @dataProvider providerTestGetContext
   */
  public function testGetContext($protocol, $context) {
    $request_stack = new RequestStack();
    $request = Request::create('/');
    $request->server->set('SERVER_PROTOCOL', $protocol);
    $request_stack->push($request);
    $cache_context = new ProtocolVersionCacheContext($request_stack);
    $this->assertSame($cache_context->getContext(), $context);
  }

  /**
   * Provides a list of query arguments and expected cache contexts.
   */
  public function providerTestGetContext() {
    return [
      ['HTTP/1.0', 'HTTP/1.0'],
      ['HTTP/1.1', 'HTTP/1.1'],
      ['HTTP/2.0', 'HTTP/2.0'],
      ['HTTP/3.0', 'HTTP/3.0'],
    ];
  }

}
