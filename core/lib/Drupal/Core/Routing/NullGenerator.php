<?php

/**
 * @file
 * Contains Drupal\Core\Routing\NullGenerator.
 */

namespace Drupal\Core\Routing;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * No-op implementation of a Url Generator, needed for backward compatibility.
 */
class NullGenerator implements UrlGeneratorInterface {

  public function generate($name, $parameters = array(), $absolute = FALSE) {
    throw new RouteNotFoundException();
  }

  public function setContext(RequestContext $context) {
  }

  public function getContext() {
  }
}
