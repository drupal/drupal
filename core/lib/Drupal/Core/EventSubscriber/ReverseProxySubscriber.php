<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\ReverseProxySubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Component\Utility\Settings;

/**
 * Reverse proxy subscriber for controller requests.
 */
class ReverseProxySubscriber implements EventSubscriberInterface {

  /**
   * A settings object.
   *
   * @var \Drupal\Component\Utility\Settings
   */
  protected $settings;

  /**
   * Construct the ReverseProxySubscriber.
   *
   * @param \Drupal\Component\Utility\Settings $settings
   *   The read-only settings object of this request.
   */
  public function __construct(Settings $settings) {
    $this->settings = $settings;
  }

  /**
   * Passes reverse proxy settings to current request.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestReverseProxyCheck(GetResponseEvent $event) {
    $request = $event->getRequest();
    if ($this->settings->get('reverse_proxy', 0)) {
      $reverse_proxy_header = $this->settings->get('reverse_proxy_header', 'HTTP_X_FORWARDED_FOR');
      $request::setTrustedHeaderName($request::HEADER_CLIENT_IP, $reverse_proxy_header);
      $reverse_proxy_addresses = $this->settings->get('reverse_proxy_addresses', array());
      $request::setTrustedProxies($reverse_proxy_addresses);
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestReverseProxyCheck', 10);
    return $events;
  }
}
