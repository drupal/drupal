<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\HttpKernel;

use Drupal\Core\Url;
use Drupal\Tests\ApiRequestTrait;
use Drupal\Tests\BrowserTestBase;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
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
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests X-Http-Method-Override header handling.
   */
  #[IgnoreDeprecations]
  public function testRequestSanitizer(): void {
    $url = new Url('<front>');
    $response = $this->makeApiRequest('GET', $url, []);
    $this->assertSame(200, $response->getStatusCode());

    $response = $this->makeApiRequest('POST', $url, []);
    $this->assertSame(200, $response->getStatusCode());

    $request_options = [];
    $request_options[RequestOptions::HEADERS] = [
      'X-Http-Method-Override' => 'GET',
    ];
    $this->expectDeprecation('Since symfony/http-foundation 7.4: HTTP method override is deprecated for methods GET, HEAD, CONNECT and TRACE; it will be ignored in Symfony 8.0.');
    $response = $this->makeApiRequest('POST', $url, $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('HIT', $response->getHeader('X-Drupal-Cache')[0]);

    // Clear the page cache.
    $this->rebuildAll();

    $response = $this->makeApiRequest('POST', $url, $request_options);
    $this->assertSame(400, $response->getStatusCode());
  }

}
