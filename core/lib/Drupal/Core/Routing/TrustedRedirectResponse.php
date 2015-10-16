<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\TrustedRedirectResponse.
 */

namespace Drupal\Core\Routing;

/**
 * Provides a redirect response which contains trusted URLs.
 *
 * Use this class in case you know that you want to redirect to an external URL.
 */
class TrustedRedirectResponse extends CacheableSecuredRedirectResponse {

  use LocalAwareRedirectResponseTrait;

  /**
   * A list of trusted URLs, which are safe to redirect to.
   *
   * @var string[]
   */
  protected $trustedUrls = array();

  /**
   * {@inheritdoc}
   */
  public function __construct($url, $status = 302, $headers = array()) {
    $this->trustedUrls[$url] = TRUE;
    parent::__construct($url, $status, $headers);
  }

  /**
   * Sets the target URL to a trusted URL.
   *
   * @param string $url
   *   A trusted URL.
   *
   * @return $this
   */
  public function setTrustedTargetUrl($url) {
    $this->trustedUrls[$url] = TRUE;
    return $this->setTargetUrl($url);
  }

  /**
   * {@inheritdoc}
   */
  protected function isSafe($url) {
    return !empty($this->trustedUrls[$url]) || $this->isLocal($url);
  }

}
