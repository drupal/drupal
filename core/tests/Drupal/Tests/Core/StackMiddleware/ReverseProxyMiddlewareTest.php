<?php

namespace Drupal\Tests\Core\StackMiddleware;

use Drupal\Core\Site\Settings;
use Drupal\Core\StackMiddleware\ReverseProxyMiddleware;
use Drupal\Tests\Traits\ExpectDeprecationTrait;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Unit test the reverse proxy stack middleware.
 *
 * @group StackMiddleware
 */
class ReverseProxyMiddlewareTest extends UnitTestCase {
  use ExpectDeprecationTrait;

  /**
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $mockHttpKernel;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->mockHttpKernel = $this->createMock('Symfony\Component\HttpKernel\HttpKernelInterface');
  }

  /**
   * Tests that subscriber does not act when reverse proxy is not set.
   */
  public function testNoProxy() {
    $settings = new Settings([]);
    $this->assertEquals(0, $settings->get('reverse_proxy'));

    $middleware = new ReverseProxyMiddleware($this->mockHttpKernel, $settings);
    // Mock a request object.
    $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
      ->setMethods(['setTrustedProxies'])
      ->getMock();
    // setTrustedProxies() should never fire.
    $request->expects($this->never())
      ->method('setTrustedProxies');
    // Actually call the check method.
    $middleware->handle($request);
  }

  /**
   * Tests that subscriber sets trusted headers when reverse proxy is set.
   *
   * @dataProvider reverseProxyEnabledProvider
   */
  public function testReverseProxyEnabled($provided_settings, $expected_trusted_header_set) {
    // Enable reverse proxy and add test values.
    $settings = new Settings(['reverse_proxy' => 1] + $provided_settings);
    $this->trustedHeadersAreSet($settings, $expected_trusted_header_set);
  }

  /**
   * Data provider for testReverseProxyEnabled.
   */
  public function reverseProxyEnabledProvider() {
    return [
      'Proxy with default trusted headers' => [
        ['reverse_proxy_addresses' => ['127.0.0.2', '127.0.0.3']],
        Request::HEADER_FORWARDED | Request::HEADER_X_FORWARDED_ALL,
      ],
      'Proxy with AWS trusted headers' => [
        [
          'reverse_proxy_addresses' => ['127.0.0.2', '127.0.0.3'],
          'reverse_proxy_trusted_headers' => Request::HEADER_X_FORWARDED_AWS_ELB,
        ],
        Request::HEADER_X_FORWARDED_AWS_ELB,
      ],
      'Proxy with custom trusted headers' => [
        [
          'reverse_proxy_addresses' => ['127.0.0.2', '127.0.0.3'],
          'reverse_proxy_trusted_headers' => Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST,
        ],
        Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST,
      ],
    ];
  }

  /**
   * Tests that trusted headers are set correctly.
   *
   * \Symfony\Component\HttpFoundation\Request::setTrustedProxies() should
   * always be called when reverse proxy settings are enabled.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings object that holds reverse proxy configuration.
   * @param int $expected_trusted_header_set
   *   The expected bit value returned by
   *   \Symfony\Component\HttpFoundation\Request::getTrustedHeaderSet()
   */
  protected function trustedHeadersAreSet(Settings $settings, $expected_trusted_header_set) {
    $middleware = new ReverseProxyMiddleware($this->mockHttpKernel, $settings);
    $request = new Request();

    $middleware->handle($request);
    $this->assertSame($settings->get('reverse_proxy_addresses'), $request->getTrustedProxies());
    $this->assertSame($expected_trusted_header_set, $request->getTrustedHeaderSet());
  }

}
