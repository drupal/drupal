<?php

/**
 * @file
 * Contains \Drupal\Core\Path\PathValidator
 */

namespace Drupal\Core\Path;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * Provides a default path validator and access checker.
 */
class PathValidator implements PathValidatorInterface {

  /**
   * The access aware router.
   *
   * @var \Drupal\Core\Routing\AccessAwareRouterInterface
   */
  protected $accessAwareRouter;

  /**
   * A router implementation which does not check access.
   *
   * @var \Symfony\Component\Routing\Matcher\UrlMatcherInterface
   */
  protected $accessUnawareRouter;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The path processor.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * Creates a new PathValidator.
   *
   * @param \Drupal\Core\Routing\AccessAwareRouterInterface $access_aware_router
   *   The access aware router.
   * @param \Symfony\Component\Routing\Matcher\UrlMatcherInterface $access_unaware_router
   *   A router implementation which does not check access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   The path processor;
   */
  public function __construct(AccessAwareRouterInterface $access_aware_router, UrlMatcherInterface $access_unaware_router, AccountInterface $account, InboundPathProcessorInterface $path_processor) {
    $this->accessAwareRouter = $access_aware_router;
    $this->accessUnawareRouter = $access_unaware_router;
    $this->account = $account;
    $this->pathProcessor = $path_processor;
  }

  /**
   * {@inheritdoc}
   */
  public function isValid($path) {
    return (bool) $this->getUrlIfValid($path);
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlIfValid($path) {
    $parsed_url = UrlHelper::parse($path);

    $options = [];
    if (!empty($parsed_url['query'])) {
      $options['query'] = $parsed_url['query'];
    }
    if (!empty($parsed_url['fragment'])) {
      $options['fragment'] = $parsed_url['fragment'];
    }

    if ($parsed_url['path'] == '<front>') {
      return new Url('<front>', [], $options);
    }
    elseif (UrlHelper::isExternal($path) && UrlHelper::isValid($path)) {
      if (empty($parsed_url['path'])) {
        return FALSE;
      }
      return Url::createFromPath($path);
    }

    $path = ltrim($path, '/');
    $request = Request::create('/' . $path);
    $attributes = $this->getPathAttributes($path, $request);

    if (!$attributes) {
      return FALSE;
    }

    $route_name = $attributes[RouteObjectInterface::ROUTE_NAME];
    $route_parameters = $attributes['_raw_variables']->all();

    return new Url($route_name, $route_parameters, $options + ['query' => $request->query->all()]);
  }

  /**
   * Gets the matched attributes for a given path.
   *
   * @param string $path
   *   The path to check.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object with the given path.
   *
   * @return array|bool
   *   An array of request attributes of FALSE if an exception was thrown.
   */
  protected function getPathAttributes($path, Request $request) {
    if ($this->account->hasPermission('link to any page')) {
      $router = $this->accessUnawareRouter;
    }
    else {
      $router = $this->accessAwareRouter;
    }

    $path = $this->pathProcessor->processInbound($path, $request);

    try {
      return $router->match('/' . $path);
    }
    catch (ResourceNotFoundException $e) {
      return FALSE;
    }
    catch (ParamNotConvertedException $e) {
      return FALSE;
    }
    catch (AccessDeniedHttpException $e) {
      return FALSE;
    }
  }

}
