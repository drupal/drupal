<?php

namespace Drupal\Core\Routing;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides helper functions for handling path changes.
 *
 * When a route's path changes, we temporarily add a route to handle the old
 * path and redirect to the new one. This temporary route is for backwards
 * compatibility (BC). If the original route is example.route, then the BC route
 * should be named example.route.bc.
 *
 * The controller for the BC route should have a deprecated annotation, a
 * deprecation error, and type declarations for any parameters that are required
 * for access checking. Then the body of the controller can use the methods
 * provided by this class:
 *
 * @code
 * $change_record = 'https://www.drupal.org/node/3320855';
 * $helper = new PathChangedHelper($route_match, $request);
 * $params = [
 *   '%old_path' => $helper->oldPath(),
 *   '%new_path' => $helper->newPath(),
 *   '%change_record' => $change_record,
 *  ];
 * $this->logger->warning('A user was redirected from %old_path. This redirect will be removed in a future version of Drupal. Update links, shortcuts, and bookmarks to use %new_path. See %change_record for more information.', $params);
 * $message = $this->t('You have been redirected from %old_path. Update links, shortcuts, and bookmarks to use %new_path.', $params);
 * $this->messenger()->addWarning($message);
 * return $helper->redirect();
 * @endcode
 */
class PathChangedHelper {

  /**
   * The URL object for the route whose path has changed.
   *
   * @var \Drupal\Core\Url
   */
  protected Url $newUrl;

  /**
   * The URL object for the BC route.
   *
   * @var \Drupal\Core\Url
   */
  protected Url $oldUrl;

  /**
   * Constructs a PathChangedHelper object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A route match object, used for the route name and the parameters.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object, used for the query parameters.
   *
   * @throws \InvalidArgumentException
   *   The route name from $route_match must end with ".bc".
   */
  public function __construct(RouteMatchInterface $route_match, Request $request) {
    $bc_route_name = $route_match->getRouteName();
    if (!str_ends_with($bc_route_name, '.bc')) {
      throw new \InvalidArgumentException(__CLASS__ . ' expects a route name that ends with ".bc".');
    }
    // Strip '.bc' from the end of the route name.
    $route_name = substr($bc_route_name, 0, -3);
    $args = $route_match->getRawParameters()->all();
    $options = [
      'absolute' => TRUE,
      'query' => array_diff_key($request->query->all(), ['destination' => '']),
    ];

    $this->newUrl = Url::fromRoute($route_name, $args, $options);
    $this->oldUrl = Url::fromRoute($bc_route_name, $args, $options);
  }

  /**
   * Returns the deprecated path.
   *
   * @return string
   *   The internal path of the old URL.
   */
  public function oldPath(): string {
    return $this->oldUrl->getInternalPath();
  }

  /**
   * Returns the updated path.
   *
   * @return string
   *   The internal path of the new URL.
   */
  public function newPath(): string {
    return $this->newUrl->getInternalPath();
  }

  /**
   * Returns a redirect to the new path.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function redirect(): RedirectResponse {
    return new RedirectResponse($this->newUrl->toString(), 301);
  }

}
