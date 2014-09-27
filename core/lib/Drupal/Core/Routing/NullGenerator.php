<?php

/**
 * @file
 * Contains Drupal\Core\Routing\NullGenerator.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RequestContext as SymfonyRequestContext;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

/**
 * No-op implementation of a Url Generator, needed for backward compatibility.
 */
class NullGenerator extends UrlGenerator {

  /**
   * Override the parent constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
    $this->context = new RequestContext();
  }

  /**
   * {@inheritdoc}
   *
   * generate(), generateFromRoute(), and getPathFromRoute() all call this
   * protected method.
   */
  protected function getRoute($name) {
    if ($name === '<front>') {
      return new Route('/');
    }
    throw new RouteNotFoundException();
  }

  /**
   * {@inheritdoc}
   */
  protected function processRoute(Route $route, array &$parameters) {
  }

  /**
   * {@inheritdoc}
   */
  protected function getInternalPathFromRoute(Route $route, $parameters = array()) {
    return $route->getPath();
  }

  /**
   * Overrides Drupal\Core\Routing\UrlGenerator::setContext();
   */
  public function setContext(SymfonyRequestContext $context) {
  }

  /**
   * Implements Symfony\Component\Routing\RequestContextAwareInterface::getContext();
   */
  public function getContext() {
  }

  /**
   * Overrides Drupal\Core\Routing\UrlGenerator::processPath().
   */
  protected function processPath($path, &$options = array()) {
    return $path;
  }
}
