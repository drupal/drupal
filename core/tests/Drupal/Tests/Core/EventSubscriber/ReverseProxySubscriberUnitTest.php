<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\ReverseProxySubscriberUnitTest.
 */

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Core\EventSubscriber\ReverseProxySubscriber;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Unit test the reverse proxy event subscriber.
 *
 * @group EventSubscriber
 */
class ReverseProxySubscriberUnitTest extends UnitTestCase {

  /**
   * Tests that subscriber does not act when reverse proxy is not set.
   */
  public function testNoProxy() {
    $settings = new Settings(array());
    $this->assertEquals(0, $settings->get('reverse_proxy'));

    $subscriber = new ReverseProxySubscriber($settings);
    // Mock a request object.
    $request = $this->getMock('Symfony\Component\HttpFoundation\Request', array('setTrustedHeaderName', 'setTrustedProxies'));
    // setTrustedHeaderName() should never fire.
    $request->expects($this->never())
      ->method('setTrustedHeaderName');
    // Mock a response event.
    $event = $this->getMockedEvent($request);
    // Actually call the check method.
    $subscriber->onKernelRequestReverseProxyCheck($event);
  }

  /**
   * Tests that subscriber sets trusted headers when reverse proxy is set.
   */
  public function testReverseProxyEnabled() {
    $cases = array(
      array(
        'reverse_proxy_header' => 'HTTP_X_FORWARDED_FOR',
        'reverse_proxy_addresses' => array(),
      ),
      array(
        'reverse_proxy_header' => 'X_FORWARDED_HOST',
        'reverse_proxy_addresses' => array('127.0.0.2', '127.0.0.3'),
      ),
    );
    foreach ($cases as $case) {
      // Enable reverse proxy and add test values.
      $settings = new Settings(array('reverse_proxy' => 1) + $case);
      $this->trustedHeadersAreSet($settings);
    }
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
    $subscriber = new ReverseProxySubscriber($settings);
    $request = new Request();

    $event = $this->getMockedEvent($request);
    $subscriber->onKernelRequestReverseProxyCheck($event);
    $this->assertSame($settings->get('reverse_proxy_header'), $request->getTrustedHeaderName($request::HEADER_CLIENT_IP));
    $this->assertSame($settings->get('reverse_proxy_addresses'), $request->getTrustedProxies());
  }

  /**
   * Creates a mocked event.
   *
   * Mocks a \Symfony\Component\HttpKernel\Event\GetResponseEvent object
   * and stubs its getRequest() method to return a mocked request object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A mocked Request object.
   *
   * @return \Symfony\Component\HttpKernel\Event\GetResponseEvent
   *   The GetResponseEvent mocked object.
   */
  protected function getMockedEvent($request) {
    $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')
      ->disableOriginalConstructor()
      ->getMock();
    $event->expects($this->once())
      ->method('getRequest')
      ->will($this->returnValue($request));
    return $event;
  }
}
