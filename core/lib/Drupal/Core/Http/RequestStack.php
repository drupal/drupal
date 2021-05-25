<?php

namespace Drupal\Core\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack as SymfonyRequestStack;

/**
 * Forward-compatibility shim for Symfony's RequestStack.
 *
 * @todo Remove when Symfony 5.3 or greater is required.
 */
class RequestStack extends SymfonyRequestStack {

  /**
   * Gets the main request.
   *
   * @return \Symfony\Component\HttpFoundation\Request|null
   *   The main request.
   */
  public function getMainRequest(): ?Request {
    if (method_exists(SymfonyRequestStack::class, 'getMainRequest')) {
      return parent::getMainRequest();
    }
    else {
      return parent::getMasterRequest();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMasterRequest() {
    @trigger_error('Drupal\Core\Http\RequestStack::getMasterRequest() is deprecated, use getMainRequest() instead.', E_USER_DEPRECATED);
    return $this->getMainRequest();
  }

}
