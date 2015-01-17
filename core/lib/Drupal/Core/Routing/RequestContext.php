<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\RequestContext.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RequestContext as SymfonyRequestContext;

/**
 * Holds information about the current request.
 *
 * @todo: Remove once the upstream RequestContext provides fromRequestStack():
 * https://github.com/symfony/symfony/issues/12057
 */
class RequestContext extends SymfonyRequestContext {

  /**
   * The scheme, host and base path, for example "http://example.com/d8".
   *
   * @var string
   */
  protected $completeBaseUrl;

  /**
   * Populates the context from the current request from the request stack.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   */
  public function fromRequestStack(RequestStack $request_stack) {
    $this->fromRequest($request_stack->getCurrentRequest());
  }

  /**
   * {@inheritdoc}
   */
  public function fromRequest(Request $request) {
    parent::fromRequest($request);

    // @todo Extract the code in DrupalKernel::initializeRequestGlobals.
    //   See https://www.drupal.org/node/2404601
    if (isset($GLOBALS['base_url'])) {
      $this->setCompleteBaseUrl($GLOBALS['base_url']);
    }
  }

  /**
   * Gets the scheme, host and base path.
   *
   * For example, in an installation in a subdirectory "d8", it should be
   * "https://example.com/d8".
   */
  public function getCompleteBaseUrl() {
    return $this->completeBaseUrl;
  }

  /**
   * Sets the complete base URL for the Request context.
   *
   * @param string $complete_base_url
   *   The complete base URL.
   */
  public function setCompleteBaseUrl($complete_base_url) {
    $this->completeBaseUrl = $complete_base_url;
  }

}
