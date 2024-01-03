<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache\Context;

use Drupal\Core\Cache\Context\HeadersCacheContext;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\Core\Cache\Context\HeadersCacheContext
 * @group Cache
 */
class HeadersCacheContextTest extends UnitTestCase {

  /**
   * @covers ::getContext
   *
   * @dataProvider providerTestGetContext
   */
  public function testGetContext($headers, $header_name, $context) {
    $request_stack = new RequestStack();
    $request = Request::create('/', 'GET');
    // Request defaults could change, so compare with default values instead of
    // passed in context value.
    $request->headers->replace($headers);
    $request_stack->push($request);
    $cache_context = new HeadersCacheContext($request_stack);
    $this->assertSame($cache_context->getContext($header_name), $context);
  }

  /**
   * Provides a list of headers and expected cache contexts.
   */
  public function providerTestGetContext() {
    return [
      [[], NULL, ''],
      [[], 'foo', ''],
      // Non-empty headers.
      [['llama' => 'rocks', 'alpaca' => '', 'panda' => 'drools', 'z' => '0'], NULL, 'alpaca=&llama=rocks&panda=drools&z=0'],
      [['llama' => 'rocks', 'alpaca' => '', 'panda' => 'drools', 'z' => '0'], 'llama', 'rocks'],
      [['llama' => 'rocks', 'alpaca' => '', 'panda' => 'drools', 'z' => '0'], 'alpaca', '?valueless?'],
      [['llama' => 'rocks', 'alpaca' => '', 'panda' => 'drools', 'z' => '0'], 'panda', 'drools'],
      [['llama' => 'rocks', 'alpaca' => '', 'panda' => 'drools', 'z' => '0'], 'z', '0'],
      [['llama' => 'rocks', 'alpaca' => '', 'panda' => 'drools', 'z' => '0'], 'chicken', ''],
      // Header value could be an array.
      [['z' => ['0', '1']], NULL, 'z=0,1'],
      // Values are sorted to minimize cache variations.
      [['z' => ['1', '0'], 'a' => []], NULL, 'a=&z=0,1'],
      [['a' => [], 'z' => ['1', '0']], NULL, 'a=&z=0,1'],
    ];
  }

}
