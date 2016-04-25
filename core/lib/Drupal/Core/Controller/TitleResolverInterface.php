<?php

namespace Drupal\Core\Controller;

use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a class which knows how to generate the title from a given route.
 */
interface TitleResolverInterface {

  /**
   * Returns a static or dynamic title for the route.
   *
   * If the returned title can contain HTML that should not be escaped it should
   * return a render array, for example:
   * @code
   * ['#markup' => 'title', '#allowed_tags' => ['em']]
   * @endcode
   * If the method returns a string and it is not marked safe then it will be
   * auto-escaped.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object passed to the title callback.
   * @param \Symfony\Component\Routing\Route $route
   *   The route information of the route to fetch the title.
   *
   * @return array|string|null
   *   The title for the route.
   */
  public function getTitle(Request $request, Route $route);

}
