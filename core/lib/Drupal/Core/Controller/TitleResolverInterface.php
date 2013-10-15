<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\TitleResolverInterface
 */
namespace Drupal\Core\Controller;

use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a class which knows how to generate the title from a given route.
 */
interface TitleResolverInterface {

  /**
   * Returns the title from a static or dynamic title for the route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object passed to the title callback.
   * @param \Symfony\Component\Routing\Route $route
   *   The route information of the route to fetch the title.
   *
   * @return string|null
   *   The title for the route.
   */
  public function getTitle(Request $request, Route $route);

}
