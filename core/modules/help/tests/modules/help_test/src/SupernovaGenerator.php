<?php

namespace Drupal\help_test;

use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Implements a URL generator which always thrown an exception.
 */
class SupernovaGenerator implements UrlGeneratorInterface {

  /**
   * {@inheritdoc}
   */
  public function setContext(RequestContext $context) {
    throw new \Exception();
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(): RequestContext {
    throw new \Exception();
  }

  /**
   * {@inheritdoc}
   */
  public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string {
    throw new \Exception();
  }

  /**
   * {@inheritdoc}
   */
  public function getPathFromRoute($name, $parameters = []) {
    throw new \Exception();
  }

  /**
   * {@inheritdoc}
   */
  public function generateFromRoute($name, $parameters = [], $options = [], $collect_bubbleable_metadata = FALSE) {
    throw new \Exception();
  }

}
