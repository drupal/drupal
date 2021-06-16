<?php

namespace Drupal\media;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\PrivateKey;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Site\Settings;

/**
 * Providers helper functions for displaying oEmbed resources in an iFrame.
 *
 * @internal
 *   This is an internal part of the oEmbed system and should only be used by
 *   oEmbed-related code in Drupal core.
 */
class IFrameUrlHelper {

  /**
   * The request context service.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * The private key service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * IFrameUrlHelper constructor.
   *
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context service.
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key service.
   */
  public function __construct(RequestContext $request_context, PrivateKey $private_key) {
    $this->requestContext = $request_context;
    $this->privateKey = $private_key;
  }

  /**
   * Hashes an oEmbed resource URL.
   *
   * @param string $url
   *   The resource URL.
   * @param int $max_width
   *   (optional) The maximum width of the resource.
   * @param int $max_height
   *   (optional) The maximum height of the resource.
   *
   * @return string
   *   The hashed URL.
   */
  public function getHash($url, $max_width = NULL, $max_height = NULL) {
    return Crypt::hmacBase64("$url:$max_width:$max_height", $this->privateKey->get() . Settings::getHashSalt());
  }

  /**
   * Checks if an oEmbed URL can be securely displayed in an frame.
   *
   * @param string $url
   *   The URL to check.
   *
   * @return bool
   *   TRUE if the URL is considered secure, otherwise FALSE.
   */
  public function isSecure($url) {
    if (!$url) {
      return FALSE;
    }
    $url_host = parse_url($url, PHP_URL_HOST);
    $system_host = parse_url($this->requestContext->getCompleteBaseUrl(), PHP_URL_HOST);

    // The URL is secure if its domain is not the same as the domain of the base
    // URL of the current request.
    return $url_host && $system_host && $url_host !== $system_host;
  }

}
