<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache\Context;

use Drupal\Core\Cache\Context\QueryArgsCacheContext;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests Drupal\Core\Cache\Context\QueryArgsCacheContext.
 */
#[CoversClass(QueryArgsCacheContext::class)]
#[Group('Cache')]
class QueryArgsCacheContextTest extends UnitTestCase {

  /**
   * Tests get context.
   */
  #[DataProvider('providerTestGetContext')]
  public function testGetContext(array $query_args, $cache_context_parameter, $context): void {
    $request_stack = new RequestStack();
    $request = Request::create('/', 'GET', $query_args);
    $request_stack->push($request);
    $cache_context = new QueryArgsCacheContext($request_stack);
    $this->assertSame($cache_context->getContext($cache_context_parameter), $context);
  }

  /**
   * Provides a list of query arguments and expected cache contexts.
   */
  public static function providerTestGetContext(): array {
    return [
      [[], NULL, ''],
      [[], 'foo', ''],
      // Non-empty query arguments.
      [
        ['llama' => 'rocks', 'alpaca' => '', 'panda' => 'drools', 'z' => '0'],
        NULL,
        'alpaca=&llama=rocks&panda=drools&z=0',
      ],
      [
        ['llama' => 'rocks', 'alpaca' => '', 'panda' => 'drools', 'z' => '0'],
        'llama',
        'rocks',
      ],
      [
        ['llama' => 'rocks', 'alpaca' => '', 'panda' => 'drools', 'z' => '0'],
        'alpaca',
        '?valueless?',
      ],
      [
        ['llama' => 'rocks', 'alpaca' => '', 'panda' => 'drools', 'z' => '0'],
        'panda',
        'drools',
      ],
      [
        ['llama' => 'rocks', 'alpaca' => '', 'panda' => 'drools', 'z' => '0'],
        'z',
        '0',
      ],
      [
        ['llama' => 'rocks', 'alpaca' => '', 'panda' => 'drools', 'z' => '0'],
        'chicken',
        '',
      ],
      [['llama' => ['rocks', 'kitty']], 'llama', '0=rocks&1=kitty'],
      [
        ['llama' => ['rocks' => 'fuzzball', 'monkey' => 'patch']],
        'llama',
        'rocks=fuzzball&monkey=patch',
      ],
      [
        ['llama' => ['rocks' => ['nested', 'bonobo']]],
        'llama',
        'rocks%5B0%5D=nested&rocks%5B1%5D=bonobo',
      ],
    ];
  }

}
