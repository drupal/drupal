<?php

namespace Drupal\Tests\Core\StackMiddleware;

use Drupal\Core\Site\Settings;
use Drupal\Core\StackMiddleware\ReverseProxyMiddleware;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Unit test the reverse proxy stack middleware.
 *
 * @group StackMiddleware
 */
class ReverseProxyMiddlewareTest extends UnitTestCase {

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
    $request = $this->getMock('Symfony\Component\HttpFoundation\Request', ['setTrustedHeaderName', 'setTrustedProxies']);
    // setTrustedHeaderName() should never fire.
    $request->expects($this->never())
      ->method('setTrustedHeaderName');
    // Actually call the check method.
    $middleware->handle($request);
  }

  /**
   * Tests that subscriber sets trusted headers when reverse proxy is set.
   *
   * @dataProvider reverseProxyEnabledProvider
   *
   * @group legacy
   *
   * @expectedDeprecation The "Symfony\Component\HttpFoundation\Request::setTrustedHeaderName()" method is deprecated since Symfony 3.3 and will be removed in 4.0. Use the $trustedHeaderSet argument of the Request::setTrustedProxies() method instead.
   */
  public function testReverseProxyEnabled($provided_settings) {
    // Enable reverse proxy and add test values.
    $settings = new Settings(['reverse_proxy' => 1] + $provided_settings);
    $this->trustedHeadersAreSet($settings);
  }

  /**
   * Data provider for testReverseProxyEnabled.
   */
  public function reverseProxyEnabledProvider() {
    return [
      [
        [
          'reverse_proxy_header' => 'X_FORWARDED_FOR_CUSTOMIZED',
          'reverse_proxy_proto_header' => 'X_FORWARDED_PROTO_CUSTOMIZED',
          'reverse_proxy_host_header' => 'X_FORWARDED_HOST_CUSTOMIZED',
          'reverse_proxy_port_header' => 'X_FORWARDED_PORT_CUSTOMIZED',
          'reverse_proxy_forwarded_header' => 'FORWARDED_CUSTOMIZED',
          'reverse_proxy_addresses' => ['127.0.0.2', '127.0.0.3'],
        ],
      ],
    ];
  }

  /**
   * Tests that trusted header methods are called.
   *
   * \Symfony\Component\HttpFoundation\Request::setTrustedHeaderName() and
   * \Symfony\Component\HttpFoundation\Request::setTrustedProxies() should
   * always be called when reverse proxy settings are enabled.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings object that holds reverse proxy configuration.
   */
  protected function trustedHeadersAreSet(Settings $settings) {
    $middleware = new ReverseProxyMiddleware($this->mockHttpKernel, $settings);
    $request = new Request();

    $middleware->handle($request);
    $this->assertSame($settings->get('reverse_proxy_header'), $request->getTrustedHeaderName($request::HEADER_X_FORWARDED_FOR));
    $this->assertSame($settings->get('reverse_proxy_proto_header'), $request->getTrustedHeaderName($request::HEADER_X_FORWARDED_PROTO));
    $this->assertSame($settings->get('reverse_proxy_host_header'), $request->getTrustedHeaderName($request::HEADER_X_FORWARDED_HOST));
    $this->assertSame($settings->get('reverse_proxy_port_header'), $request->getTrustedHeaderName($request::HEADER_X_FORWARDED_PORT));
    $this->assertSame($settings->get('reverse_proxy_forwarded_header'), $request->getTrustedHeaderName($request::HEADER_FORWARDED));
    $this->assertSame($settings->get('reverse_proxy_addresses'), $request->getTrustedProxies());
  }

}
