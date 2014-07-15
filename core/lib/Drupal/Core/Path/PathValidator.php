<?php

/**
 * @file
 * Contains Drupal\Core\Path\PathValidator
 */

namespace Drupal\Core\Path;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Access\AccessManager;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Routing\RequestHelper;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;
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
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManager
   */
  protected $accessManager;

  /**
   * The user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Creates a new PathValidator.
   *
   * @param \Symfony\Component\Routing\Matcher\RequestMatcherInterface $request_matcher
   *   The request matcher.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Access\AccessManager $access_manager
   *   The access manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   */
  public function __construct(RequestMatcherInterface $request_matcher, RouteProviderInterface $route_provider, RequestStack $request_stack, AccessManager $access_manager, AccountInterface $account) {
    $this->requestMatcher = $request_matcher;
    $this->routeProvider = $route_provider;
    $this->requestStack = $request_stack;
    $this->accessManager = $access_manager;
    $this->account = $account;
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

    $request = RequestHelper::duplicate($this->requestStack->getCurrentRequest(), '/' . $path);
    $request->attributes->set('_system_path', $path);

    // We indicate that a menu administrator is running the menu access check.
    $request->attributes->set('_menu_admin', TRUE);

    // Attempt to match this path to provide a fully built request to the
    // access checker.
    try {
      $request->attributes->add($this->requestMatcher->matchRequest($request));
    }
    catch (ParamNotConvertedException $e) {
      return FALSE;
    }

    // Consult the access manager.
    $routes = $collection->all();
    $route = reset($routes);
    return $this->accessManager->check($route, $request, $this->account);
  }

}
