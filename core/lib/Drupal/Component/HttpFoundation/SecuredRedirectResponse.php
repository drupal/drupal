<?php

/**
 * @file
 * Contains \Drupal\Component\HttpFoundation\SecuredRedirectResponse.
 */

namespace Drupal\Component\HttpFoundation;

use \Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a common base class for safe redirects.
 *
 * In case you want to redirect to external URLs use
 * TrustedRedirectResponse.
 *
 * For local URLs we use LocalRedirectResponse which opts
 * out of external redirects.
 */
abstract class SecuredRedirectResponse extends RedirectResponse {

  /**
   * Copies an existing redirect response into a safe one.
   *
   * The safe one cannot accidentally redirect to an external URL, unless
   * actively wanted (see TrustedRedirectResponse).
   *
   * @param \Symfony\Component\HttpFoundation\RedirectResponse $response
   *   The original redirect.
   *
   * @return static
   */
  public static function createFromRedirectResponse(RedirectResponse $response) {
    $safe_response = new static($response->getTargetUrl(), $response->getStatusCode(), $response->headers->allPreserveCase());
    $safe_response->fromResponse($response);
    return $safe_response;
  }

  /**
   * Copies over the values from the given response.
   *
   * @param \Symfony\Component\HttpFoundation\RedirectResponse $response
   *   The redirect reponse object.
   */
  protected function fromResponse(RedirectResponse $response) {
    $this->setProtocolVersion($response->getProtocolVersion());
    $this->setCharset($response->getCharset());
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetUrl($url) {
    if (!$this->isSafe($url)) {
      throw new \InvalidArgumentException(sprintf('It is not safe to redirect to %s', $url));
    }
    return parent::setTargetUrl($url);
  }

  /**
   * Returns whether the URL is considered as safe to redirect to.
   *
   * @param string $url
   *   The URL checked for safety.
   *
   * @return bool
   */
  abstract protected function isSafe($url);

}
