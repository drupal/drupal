<?php

namespace Drupal\Core\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack as SymfonyRequestStack;

/**
 * Forward-compatibility shim for Symfony's RequestStack.
 *
 * @todo https://www.drupal.org/node/3265121 Remove in Drupal 10.0.x.
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
    @trigger_error(__METHOD__ . '() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use getMainRequest() instead. See https://www.drupal.org/node/3253744', E_USER_DEPRECATED);
    return $this->getMainRequest();
  }

}
