<?php

namespace Drupal\Core\Controller;

use Drupal\Core\Menu\LocalTaskManager;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Utility\RequestGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

/**
 * Provides a class which gets the title from the current local-task base route.
 */
class BaseRouteTitleResolver implements TitleResolverInterface {

  /**
   * Constructs a RequestGenerator object.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $urlGenerator
   *   The url generator.
   * @param \Drupal\Core\Controller\TitleResolverInterface $titleResolver
   *   The title resolver.
   * @param \Drupal\Core\Menu\LocalTaskManager $localTaskManager
   *   The local task manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $routeProvider
   *   The route provider.
   * @param \Drupal\Core\Utility\RequestGenerator $requestGenerator
   *   The request generator.
   */
  public function __construct(
    protected UrlGeneratorInterface $urlGenerator,
    protected TitleResolverInterface $titleResolver,
    protected LocalTaskManager $localTaskManager,
    protected RouteProviderInterface $routeProvider,
    protected RequestGenerator $requestGenerator,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request, Route $route) : array|string|\Stringable|null {
    $route_match = RouteMatch::createFromRequest($request);
    $base_route_names = $this->localTaskManager->getBaseRouteNames($route_match->getRouteName());
    $title = NULL;
    if ($base_route_names) {
      $base_route_name = reset($base_route_names);
      if ($base_route_name !== $route_match->getRouteName()) {
        try {
          $path = $this->urlGenerator->getPathFromRoute($base_route_name, $route_match->getRawParameters()->all());
        }
        catch (RouteNotFoundException | InvalidParameterException) {
          return NULL;
        }
        $route_request = $this->requestGenerator->generateRequestForPath($path, []);
        if ($route_request) {
          $title = $this->titleResolver->getTitle($route_request, $this->routeProvider->getRouteByName($base_route_name));
        }
      }
    }
    return $title;
  }

}
