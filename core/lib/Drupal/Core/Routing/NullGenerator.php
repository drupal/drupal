<?php

/**
 * @file
 * Contains Drupal\Core\Routing\NullGenerator.
 */

namespace Drupal\Core\Routing;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * No-op implementation of a Url Generator, needed for backward compatibility.
 */
class NullGenerator implements UrlGeneratorInterface {

  public function generate($name, $parameters = array(), $absolute = FALSE) {
    throw new \Exception('Method disabled. This is a dummy implementation.');
  }

  public function setContext(RequestContext $context) {
    throw new \Exception('Method disabled. This is a dummy implementation.');
  }

  public function getContext() {
    throw new \Exception('Method disabled. This is a dummy implementation.');
  }
}
