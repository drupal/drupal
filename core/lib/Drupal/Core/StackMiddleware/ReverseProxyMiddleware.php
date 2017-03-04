<?php

namespace Drupal\Core\StackMiddleware;

use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Provides support for reverse proxies.
 */
class ReverseProxyMiddleware implements HttpKernelInterface {

  /**
   * The decorated kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The site settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * Constructs a ReverseProxyMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param \Drupal\Core\Site\Settings $settings
   *   The site settings.
   */
  public function __construct(HttpKernelInterface $http_kernel, Settings $settings) {
    $this->httpKernel = $http_kernel;
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    // Initialize proxy settings.
    static::setSettingsOnRequest($request, $this->settings);
    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * Sets reverse proxy settings on Request object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A Request instance.
   * @param \Drupal\Core\Site\Settings $settings
   *   The site settings.
   */
  public static function setSettingsOnRequest(Request $request, Settings $settings) {
    // Initialize proxy settings.
    if ($settings->get('reverse_proxy', FALSE)) {
      $ip_header = $settings->get('reverse_proxy_header', 'X_FORWARDED_FOR');
      $request::setTrustedHeaderName($request::HEADER_CLIENT_IP, $ip_header);

      $proto_header = $settings->get('reverse_proxy_proto_header', 'X_FORWARDED_PROTO');
      $request::setTrustedHeaderName($request::HEADER_CLIENT_PROTO, $proto_header);

      $host_header = $settings->get('reverse_proxy_host_header', 'X_FORWARDED_HOST');
      $request::setTrustedHeaderName($request::HEADER_CLIENT_HOST, $host_header);

      $port_header = $settings->get('reverse_proxy_port_header', 'X_FORWARDED_PORT');
      $request::setTrustedHeaderName($request::HEADER_CLIENT_PORT, $port_header);

      $forwarded_header = $settings->get('reverse_proxy_forwarded_header', 'FORWARDED');
      $request::setTrustedHeaderName($request::HEADER_FORWARDED, $forwarded_header);

      $proxies = $settings->get('reverse_proxy_addresses', []);
      if (count($proxies) > 0) {
        $request::setTrustedProxies($proxies);
      }
    }
  }

}
