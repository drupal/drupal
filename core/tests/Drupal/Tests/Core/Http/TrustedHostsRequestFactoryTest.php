<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Http;

use Drupal\Core\Http\TrustedHostsRequestFactory;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the trusted hosts request factory.
 *
 * @coversDefaultClass \Drupal\Core\Http\TrustedHostsRequestFactory
 * @group Http
 */
class TrustedHostsRequestFactoryTest extends UnitTestCase {

  /**
   * Tests TrustedHostsRequestFactory::createRequest().
   *
   * @param string $host
   *   The host to pass into TrustedHostsRequestFactory.
   * @param array $server
   *   The server array to pass into
   *   TrustedHostsRequestFactory::createRequest().
   * @param string $expected
   *   The expected host of the created request.
   *
   * @covers ::createRequest
   * @dataProvider providerTestCreateRequest
   */
  public function testCreateRequest($host, $server, $expected): void {
    $request_factory = new TrustedHostsRequestFactory($host);
    $request = $request_factory->createRequest([], [], [], [], [], $server, []);
    $this->assertEquals($expected, $request->getHost());
  }

  /**
   * Provides data for testCreateRequest().
   *
   * @return array
   *   An array of test cases, where each test case is an array with the
   *   following values:
   *   - A string containing the host to pass into TrustedHostsRequestFactory.
   *   - An array containing the server array to pass into
   *   TrustedHostsRequestFactory::createRequest().
   *   - A string containing the expected host of the created request.
   */
  public static function providerTestCreateRequest() {
    $tests = [];
    $tests[] = ['example.com', [], 'example.com'];
    $tests[] = ['localhost', [], 'localhost'];
    $tests[] = ['localhost', ['HTTP_HOST' => 'localhost'], 'localhost'];
    $tests[] = ['example.com', ['HTTP_HOST' => 'localhost'], 'example.com'];
    return $tests;
  }

}
