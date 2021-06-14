<?php

namespace Drupal\Core\Path;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Represents the current path for the current request.
 *
 * Note: You should not rely on paths but rather on route names / parameters or
 *   other indicators like context. For some fundamental parts, like routing or
 *   path processing, there is unfortunately no way around dealing with paths.
 */
class CurrentPathStack {

  /**
   * Static cache of paths.
   *
   * @var \SplObjectStorage
   */
  protected $paths;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new CurrentPathStack instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
    $this->paths = new \SplObjectStorage();
  }

  /**
   * Returns the path of the current request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   (optional) The request.
   *
   * @return string
   *   Returns the path, without leading slashes.
   */
  public function getPath(Request $request = NULL) {
    if (!isset($request)) {
      $request = $this->requestStack->getCurrentRequest();
    }
    if (!isset($this->paths[$request])) {
      $this->paths[$request] = $request->getPathInfo();
    }

    return $this->paths[$request];
  }

  /**
   * Sets the current path.
   *
   * @param string $path
   *   The path.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   (optional) The request.
   *
   * @return $this
   */
  public function setPath($path, Request $request = NULL) {
    if (!isset($request)) {
      $request = $this->requestStack->getCurrentRequest();
    }
    $this->paths[$request] = $path;

    return $this;
  }

}
