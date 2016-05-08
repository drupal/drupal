<?php

namespace Drupal\Core\Routing;

use Drupal\Core\Render\BubbleableMetadata;
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
    elseif ($name === '<current>') {
      return new Route($this->requestStack->getCurrentRequest()->getPathInfo());
    }
    elseif ($name === '<none>') {
      return new Route('');
    }
    throw new RouteNotFoundException();
  }

  /**
   * {@inheritdoc}
   */
  protected function processRoute($name, Route $route, array &$parameters, BubbleableMetadata $bubbleable_metadata = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  protected function getInternalPathFromRoute($name, Route $route, $parameters = array(), $query_params = array()) {
    return $route->getPath();
  }

  /**
   * {@inheritdoc}
   */
  public function setContext(SymfonyRequestContext $context) {
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
  }

  /**
   * {@inheritdoc}
   */
  protected function processPath($path, &$options = array(), BubbleableMetadata $bubbleable_metadata = NULL) {
    return $path;
  }

}
