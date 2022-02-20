<?php

namespace Drupal\Core\Http;

@trigger_error('The ' . __NAMESPACE__ . '\RequestStack is deprecated in drupal:10.0.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3265357', E_USER_DEPRECATED);

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack as SymfonyRequestStack;

/**
 * Forward-compatibility shim for Symfony's RequestStack.
 *
 * @deprecated in drupal:10.0.0 and is removed from drupal:11.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3265357
 *
 * @todo Remove this in Drupal 11 https://www.drupal.org/node/3265121
 */
class RequestStack extends SymfonyRequestStack {

  /**
   * Gets the main request.
   *
   * @return \Symfony\Component\HttpFoundation\Request|null
   *   The main request.
   */
  public function getMainRequest(): ?Request {
    @trigger_error('The ' . __NAMESPACE__ . '\RequestStack is deprecated in drupal:10.0.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3265357', E_USER_DEPRECATED);
    return parent::getMainRequest();
  }

}
