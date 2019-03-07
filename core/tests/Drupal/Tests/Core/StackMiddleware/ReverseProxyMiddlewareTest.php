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
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $mockHttpKernel;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->mockHttpKernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
  }

  /**
   * Tests that subscriber does not act when reverse proxy is not set.
   */
  public function testNoProxy() {
    $settings = new Settings([]);
    $this->assertEquals(0, $settings->get('reverse_proxy'));

    $middleware = new ReverseProxyMiddleware($this->mockHttpKernel, $settings);
    // Mock a request object.
    $request = $this->getMock('Symfony\Component\HttpFoundation\Request', ['setTrustedProxies']);
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
   * Tests that subscriber sets trusted headers when reverse proxy is set.
   *
   * @dataProvider reverseProxyEnabledProviderLegacy
   * @group legacy
   */
  public function testReverseProxyEnabledLegacy($provided_settings, $expected_trusted_header_set, array $expected_deprecations) {
    if (!method_exists(Request::class, 'setTrustedHeaderName')) {
      $this->markTestSkipped('The method \Symfony\Component\HttpFoundation\Request::setTrustedHeaderName() does not exist therefore testing on Symfony 4 or greater.');
    }
    $this->expectedDeprecations($expected_deprecations);
    // Enable reverse proxy and add test values.
    $settings = new Settings(['reverse_proxy' => 1] + $provided_settings);
    $this->trustedHeadersAreSet($settings, $expected_trusted_header_set);
  }

  /**
   * Data provider for testReverseProxyEnabled.
   */
  public function reverseProxyEnabledProviderLegacy() {
    return [
      'Proxy with deprecated custom headers' => [
        [
          'reverse_proxy_addresses' => ['127.0.0.2', '127.0.0.3'],
          'reverse_proxy_host_header' => NULL,
          'reverse_proxy_forwarded_header' => NULL,
        ],
        // For AWS configuration forwarded and x_forwarded_host headers are not
        // trusted.
        Request::HEADER_X_FORWARDED_AWS_ELB,
        [
          'The \'reverse_proxy_host_header\' setting in settings.php is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use the \'reverse_proxy_trusted_headers\' setting instead. See https://www.drupal.org/node/3030558',
          'The \'reverse_proxy_forwarded_header\' setting in settings.php is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use the \'reverse_proxy_trusted_headers\' setting instead. See https://www.drupal.org/node/3030558',
          'The "Symfony\Component\HttpFoundation\Request::setTrustedHeaderName()" method is deprecated since Symfony 3.3 and will be removed in 4.0. Use the $trustedHeaderSet argument of the Request::setTrustedProxies() method instead.',
        ],
      ],
      'Proxy with deprecated custom header' => [
        [
          'reverse_proxy_addresses' => ['127.0.0.2', '127.0.0.3'],
          'reverse_proxy_forwarded_header' => NULL,
        ],
        // The forwarded header is not trusted which is the same as trusting all
        // the x_forwarded headers.
        Request::HEADER_X_FORWARDED_ALL,
        [
          'The \'reverse_proxy_forwarded_header\' setting in settings.php is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use the \'reverse_proxy_trusted_headers\' setting instead. See https://www.drupal.org/node/3030558',
          'The "Symfony\Component\HttpFoundation\Request::setTrustedHeaderName()" method is deprecated since Symfony 3.3 and will be removed in 4.0. Use the $trustedHeaderSet argument of the Request::setTrustedProxies() method instead.',
        ],
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
