<?php

/**
 * @file
 * Contains Drupal\Core\Path\PathValidator
 */

namespace Drupal\Core\Path;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Routing\RequestHelper;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * Provides a default path validator and access checker.
 */
class PathValidator implements PathValidatorInterface {

  /**
   * The request matcher.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected $requestMatcher;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Creates a new PathValidator.
   *
   * @param \Symfony\Component\Routing\Matcher\RequestMatcherInterface $request_matcher
   *   The request matcher.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestMatcherInterface $request_matcher, RouteProviderInterface $route_provider, RequestStack $request_stack) {
    $this->requestMatcher = $request_matcher;
    $this->routeProvider = $route_provider;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function isValid($path) {
    // External URLs and the front page are always valid.
    if ($path == '<front>' || UrlHelper::isExternal($path)) {
      return TRUE;
    }

    // Check the routing system.
    $collection = $this->routeProvider->getRoutesByPattern('/' . $path);
    if ($collection->count() == 0) {
      return FALSE;
    }

    // We can not use $this->requestMatcher->match() because we need to set
    // the _menu_admin attribute to indicate a menu administrator is running
    // the menu access check.
    $request = RequestHelper::duplicate($this->requestStack->getCurrentRequest(), '/' . $path);
    $request->attributes->set('_system_path', $path);
    $request->attributes->set('_menu_admin', TRUE);

    try {
      $this->requestMatcher->matchRequest($request);
    }
    catch (ParamNotConvertedException $e) {
      return FALSE;
    }
    catch (AccessDeniedHttpException $e) {
      return FALSE;
    }
    return TRUE;
  }

}
