<?php

namespace Drupal\Tests\Core\Cache\Context;

use Drupal\Core\Cache\Context\QueryArgsCacheContext;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\Core\Cache\Context\QueryArgsCacheContext
 * @group Cache
 */
class QueryArgsCacheContextTest extends UnitTestCase {

  /**
   * @covers ::getContext
   *
   * @dataProvider providerTestGetContext
   */
  public function testGetContext(array $query_args, $cache_context_parameter, $context) {
    $request_stack = new RequestStack();
    $request = Request::create('/', 'GET', $query_args);
    $request_stack->push($request);
    $cache_context = new QueryArgsCacheContext($request_stack);
    $this->assertSame($cache_context->getContext($cache_context_parameter), $context);
  }

  /**
   * Provides a list of query arguments and expected cache contexts.
   */
  public function providerTestGetContext() {
    return [
      [[], NULL, NULL],
      [[], 'foo', NULL],
      // Non-empty query arguments.
      [['llama' => 'rocks', 'alpaca' => '', 'panda' => 'drools', 'z' => '0'], NULL, 'alpaca=&llama=rocks&panda=drools&z=0'],
      [['llama' => 'rocks', 'alpaca' => '', 'panda' => 'drools', 'z' => '0'], 'llama', 'rocks'],
      [['llama' => 'rocks', 'alpaca' => '', 'panda' => 'drools', 'z' => '0'], 'alpaca', '?valueless?'],
      [['llama' => 'rocks', 'alpaca' => '', 'panda' => 'drools', 'z' => '0'], 'panda', 'drools'],
      [['llama' => 'rocks', 'alpaca' => '', 'panda' => 'drools', 'z' => '0'], 'z', '0'],
      [['llama' => 'rocks', 'alpaca' => '', 'panda' => 'drools', 'z' => '0'], 'chicken', NULL],
    ];
  }

}
