<?php

/**
 * @file
 * Contains Drupal\Core\Routing\UrlMatcher.
 */

namespace Drupal\Core\Routing;

use Drupal\Core\Path\CurrentPathStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Cmf\Component\Routing\NestedMatcher\UrlMatcher as BaseUrlMatcher;

/**
 * Drupal-specific URL Matcher; handles the Drupal "system path" mapping.
 */
class UrlMatcher extends BaseUrlMatcher {

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Constructs a new UrlMatcher.
   *
   * The parent class has a constructor we need to skip, so just override it
   * with a no-op.
   *
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   */
  public function __construct(CurrentPathStack $current_path) {
    $this->currentPath = $current_path;
  }

  public function finalMatch(RouteCollection $collection, Request $request) {
    $this->routes = $collection;
    $context = new RequestContext();
    $context->fromRequest($request);
    $this->setContext($context);

    return $this->match($this->currentPath->getPath($request));
  }

}
