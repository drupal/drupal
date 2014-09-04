<?php
/**
 * @file
 * Contains \Drupal\Core\StackMiddleware\ReverseProxyMiddleware
 */

namespace Drupal\Core\StackMiddleware;

use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 *
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
    if ($this->settings->get('reverse_proxy', FALSE)) {
      $reverse_proxy_header = $this->settings->get('reverse_proxy_header', 'X_FORWARDED_FOR');
      $request::setTrustedHeaderName($request::HEADER_CLIENT_IP, $reverse_proxy_header);
      $proxies = $this->settings->get('reverse_proxy_addresses', array());
      if (count($proxies) > 0) {
        $request::setTrustedProxies($proxies);
      }
    }
    return $this->httpKernel->handle($request, $type, $catch);
  }

}
