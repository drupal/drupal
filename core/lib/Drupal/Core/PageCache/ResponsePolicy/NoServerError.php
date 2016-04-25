<?php

namespace Drupal\Core\PageCache\ResponsePolicy;

use Drupal\Core\PageCache\ResponsePolicyInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A policy denying caching of a server error (HTTP 5xx) responses.
 */
class NoServerError implements ResponsePolicyInterface {

  /**
   * {@inheritdoc}
   */
  public function check(Response $response, Request $request) {
    if ($response->isServerError()) {
      return static::DENY;
    }
  }

}
