<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache\Context;

use Drupal\Core\Cache\Context\ProtocolVersionCacheContext;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests Drupal\Core\Cache\Context\ProtocolVersionCacheContext.
 */
#[CoversClass(ProtocolVersionCacheContext::class)]
#[Group('Cache')]
class ProtocolVersionCacheContextTest extends UnitTestCase {

  /**
   * Tests get context.
   */
  #[DataProvider('providerTestGetContext')]
  public function testGetContext($protocol, $context): void {
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
  public static function providerTestGetContext(): array {
    return [
      ['HTTP/1.0', 'HTTP/1.0'],
      ['HTTP/1.1', 'HTTP/1.1'],
      ['HTTP/2.0', 'HTTP/2.0'],
      ['HTTP/3.0', 'HTTP/3.0'],
    ];
  }

}
