<?php

/**
 * @file
 * Contains \Drupal\help_test\SuperNovaGenerator.
 */

namespace Drupal\help_test;

use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Implements a URL generator which always thrown an exception.
 */
class SuperNovaGenerator implements UrlGeneratorInterface {

  /**
   * {@inheritdoc}
   */
  public function setContext(RequestContext $context) {
    throw new \Exception();
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    throw new \Exception();
  }

  /**
   * {@inheritdoc}
   */
  public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH) {
    throw new \Exception();
  }

  /**
   * {@inheritdoc}
   */
  public function generateFromPath($path = NULL, $options = array()) {
    throw new \Exception();
  }

  /**
   * {@inheritdoc}
   */
  public function getPathFromRoute($name, $parameters = array()) {
    throw new \Exception();
  }

  /**
   * {@inheritdoc}
   */
  public function generateFromRoute($name, $parameters = array(), $options = array()) {
    throw new \Exception();
  }

  /**
   * {@inheritdoc}
   */
  public function supports($name) {
    throw new \Exception();
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteDebugMessage($name, array $parameters = array()) {
    throw new \Exception();
  }

}
