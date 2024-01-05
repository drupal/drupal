<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\XdebugRequestTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides tests for the Xdebug request trait.
 *
 * @coversDefaultClass \Drupal\Tests\XdebugRequestTrait
 * @group Test
 */
class XdebugRequestTraitTest extends UnitTestCase {

  use XdebugRequestTrait;

  /**
   * Tests that Xdebug cookies are extracted from a request correctly.
   *
   * @param array $server
   *   The request server array.
   * @param array $expected_cookies
   *   The expected cookies for the request.
   *
   * @covers ::extractCookiesFromRequest
   * @dataProvider extractCookiesDataProvider
   */
  public function testExtractCookiesFromRequest(array $server, array $expected_cookies): void {
    $request = new Request([], [], [], [], [], $server);
    $this->assertSame($expected_cookies, $this->extractCookiesFromRequest($request));
  }

  /**
   * Provides data to test extracting Xdebug cookies from a request.
   *
   * @return iterable
   *   Test scenarios.
   */
  public function extractCookiesDataProvider() {
    yield 'no XDEBUG_CONFIG' => [[], []];
    yield 'empty string XDEBUG_CONFIG' => [['XDEBUG_CONFIG' => ''], []];
    yield 'only space string XDEBUG_CONFIG' => [['XDEBUG_CONFIG' => ' '], []];
    yield 'invalid XDEBUG_CONFIG' => [['XDEBUG_CONFIG' => 'invalid_config'], []];
    yield 'idekey XDEBUG_CONFIG' => [
      ['XDEBUG_CONFIG' => 'idekey=XDEBUG_KEY'],
      ['XDEBUG_SESSION' => ['XDEBUG_KEY']],
    ];
    yield 'idekey with another key XDEBUG_CONFIG' => [
      ['XDEBUG_CONFIG' => 'foo=bar  idekey=XDEBUG_KEY '],
      ['XDEBUG_SESSION' => ['XDEBUG_KEY']],
    ];
  }

}
