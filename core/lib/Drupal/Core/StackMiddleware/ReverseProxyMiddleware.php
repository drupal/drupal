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
      $proxies = $settings->get('reverse_proxy_addresses', []);
      if (count($proxies) > 0) {
        $deprecated_settings = [
          'reverse_proxy_header' => Request::HEADER_X_FORWARDED_FOR,
          'reverse_proxy_proto_header' => Request::HEADER_X_FORWARDED_PROTO,
          'reverse_proxy_host_header' => Request::HEADER_X_FORWARDED_HOST,
          'reverse_proxy_port_header' => Request::HEADER_X_FORWARDED_PORT,
          'reverse_proxy_forwarded_header' => Request::HEADER_FORWARDED,
        ];

        $all = $settings->getAll();
        // Set the default value. This is the most relaxed setting possible and
        // not recommended for production.
        $trusted_header_set = Request::HEADER_X_FORWARDED_ALL | Request::HEADER_FORWARDED;
        foreach ($deprecated_settings as $deprecated_setting => $bit_value) {
          if (array_key_exists($deprecated_setting, $all)) {
            @trigger_error(sprintf("The '%s' setting in settings.php is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use the 'reverse_proxy_trusted_headers' setting instead. See https://www.drupal.org/node/3030558", $deprecated_setting), E_USER_DEPRECATED);
            $request::setTrustedHeaderName($bit_value, $all[$deprecated_setting]);
            if ($all[$deprecated_setting] === NULL) {
              // If the value is NULL do not trust the header.
              $trusted_header_set &= ~$bit_value;
            }
          }
        }

        $request::setTrustedProxies(
          $proxies,
          $settings->get('reverse_proxy_trusted_headers', $trusted_header_set)
        );
      }
    }
  }

}
