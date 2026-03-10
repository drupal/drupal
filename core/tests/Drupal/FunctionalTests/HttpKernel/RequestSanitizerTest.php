<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\HttpKernel;

use Drupal\Core\Url;
use Drupal\Tests\ApiRequestTrait;
use Drupal\Tests\BrowserTestBase;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests RequestSanitizerMiddleware.
 */
#[Group('Http')]
#[RunTestsInSeparateProcesses]
class RequestSanitizerTest extends BrowserTestBase {
  use ApiRequestTrait;
  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'system_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests X-Http-Method-Override header handling.
   *
   * Drupal checks the X-HTTP-Method-Override header directly and rejects any
   * OPTIONS override. Symfony 8 silently ignores overrides to
   * GET/HEAD/CONNECT/TRACE in getMethod(), so the page cache sees the original
   * POST method (not cacheable).
   */
  public function testRequestSanitizer(): void {
    $url = new Url('system_test.method');
    $response = $this->makeApiRequest('GET', $url, []);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('GET', (string) $response->getBody());

    $response = $this->makeApiRequest('POST', $url, []);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('POST', (string) $response->getBody());

    // A POST with X-Http-Method-Override: GET is accepted. The page cache serves
    // with a 200. The header is ignored and isMethodCacheable() sees POST.
    $request_options[RequestOptions::HEADERS] = [
      'X-Http-Method-Override' => 'GET',
    ];
    $response = $this->makeApiRequest('POST', $url, $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('POST', (string) $response->getBody());

    // A POST with X-Http-Method-Override: OPTIONS is rejected with a 400 by
    // the request sanitizer.
    $request_options[RequestOptions::HEADERS] = [
      'X-Http-Method-Override' => 'OPTIONS',
    ];
    $response = $this->makeApiRequest('POST', $url, $request_options);
    $this->assertSame(400, $response->getStatusCode());

    // Verify the result is the same after clearing the page cache.
    $this->rebuildAll();

    $request_options[RequestOptions::HEADERS] = [
      'X-Http-Method-Override' => 'GET',
    ];
    $response = $this->makeApiRequest('POST', $url, $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('POST', (string) $response->getBody());

    $request_options[RequestOptions::HEADERS] = [
      'X-Http-Method-Override' => 'OPTIONS',
    ];
    $response = $this->makeApiRequest('POST', $url, $request_options);
    $this->assertSame(400, $response->getStatusCode());
  }

}
