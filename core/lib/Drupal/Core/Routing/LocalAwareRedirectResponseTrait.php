<?php

namespace Drupal\Core\Routing;

use Drupal\Component\Utility\UrlHelper;

/**
 * Provides a trait which ensures that a URL is safe to redirect to.
 */
trait LocalAwareRedirectResponseTrait {

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Determines whether a path is local.
   *
   * @param string $url
   *   The internal path or external URL being linked to, such as "node/34" or
   *   "https://example.com/foo".
   *
   * @return bool
   *   TRUE or FALSE, where TRUE indicates a local path.
   */
  protected function isLocal($url) {
    return !UrlHelper::isExternal($url) || UrlHelper::externalIsLocal($url, $this->getRequestContext()->getCompleteBaseUrl());
  }

  /**
   * Returns the request context.
   *
   * @return \Drupal\Core\Routing\RequestContext
   *   The request context.
   */
  protected function getRequestContext() {
    if (!isset($this->requestContext)) {
      $this->requestContext = \Drupal::service('router.request_context');
    }
    return $this->requestContext;
  }

  /**
   * Sets the request context.
   *
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   *
   * @return $this
   */
  public function setRequestContext(RequestContext $request_context) {
    $this->requestContext = $request_context;

    return $this;
  }

}
