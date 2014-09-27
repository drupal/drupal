<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\RequestContext.
 */

namespace Drupal\Core\Routing;

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
   * Populates the context from the current request from the request stack.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   */
  public function fromRequestStack(RequestStack $request_stack) {
    $this->fromRequest($request_stack->getCurrentRequest());
  }

}
