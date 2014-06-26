<?php

/**
 * @file
 * Functions to handle paths in Drupal.
 */

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Routing\RequestHelper;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Check if the current page is the front page.
 *
 * @return
 *   Boolean value: TRUE if the current page is the front page; FALSE if otherwise.
 */
function drupal_is_front_page() {
  // Use the advanced drupal_static() pattern, since this is called very often.
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['is_front_page'] = &drupal_static(__FUNCTION__);
  }
  $is_front_page = &$drupal_static_fast['is_front_page'];

  if (!isset($is_front_page)) {
    $is_front_page = (current_path() == \Drupal::config('system.site')->get('page.front'));
  }

  return $is_front_page;
}

/**
 * Check if a path matches any pattern in a set of patterns.
 *
 * @param $path
 *   The path to match.
 * @param $patterns
 *   String containing a set of patterns separated by \n, \r or \r\n.
 *
 * @return
 *   Boolean value: TRUE if the path matches a pattern, FALSE otherwise.
 *
 * @deprecated as of Drupal 8.0. Use
 *   \Drupal\Core\Path\PathMatcherInterface::matchPath() instead.
 */
function drupal_match_path($path, $patterns) {
  return \Drupal::service('path.matcher')->matchPath($path, $patterns);
}

/**
 * Return the current URL path of the page being viewed.
 *
 * Examples:
 * - http://example.com/node/306 returns "node/306".
 * - http://example.com/drupalfolder/node/306 returns "node/306" while
 *   base_path() returns "/drupalfolder/".
 * - http://example.com/path/alias (which is a path alias for node/306) returns
 *   "node/306" as opposed to the path alias.
 *
 * @return string
 *   The current Drupal URL path.
 *
 * @see request_path()
 */
function current_path() {
  // @todo Remove the check for whether the request service exists and the
  // fallback code below, once the path alias logic has been figured out in
  // http://drupal.org/node/1269742.
  if (\Drupal::getContainer()->isScopeActive('request')) {
    $path = \Drupal::request()->attributes->get('_system_path');
    if ($path !== NULL) {
      return $path;
    }
  }
  // If we are outside the request scope, fall back to using the path stored in
  // _current_path().
  return _current_path();
}

/**
 * Determines whether a path is in the administrative section of the site.
 *
 * By default, paths are considered to be non-administrative. If a path does
 * not match any of the patterns in path_get_admin_paths(), or if it matches
 * both administrative and non-administrative patterns, it is considered
 * non-administrative.
 *
 * @param $path
 *   A Drupal path.
 *
 * @return
 *   TRUE if the path is administrative, FALSE otherwise.
 *
 * @deprecated Use \Drupal::service('router.admin_context')->isAdminRoute()
 *   service instead.
 */
function path_is_admin($path) {
  try {
    $parameters = \Drupal::service('router')->match('/' . $path);
    $route = $parameters[RouteObjectInterface::ROUTE_OBJECT];
    return \Drupal::service('router.admin_context')->isAdminRoute($route);
  }
  catch (ParamNotConvertedException $e) {
    return FALSE;
  }
}

/**
 * Checks a path exists and the current user has access to it.
 *
 * @param string $path
 *   The path to check.
 *
 * @return bool
 *   TRUE if it is a valid path AND the current user has access permission,
 *   FALSE otherwise.
 */
function drupal_valid_path($path) {
  // External URLs and the front page are always valid.
  if ($path == '<front>' || UrlHelper::isExternal($path)) {
    return TRUE;
  }

  // Check the routing system.
  $collection = \Drupal::service('router.route_provider')->getRoutesByPattern('/' . $path);
  if ($collection->count() == 0) {
    return FALSE;
  }

  $request = RequestHelper::duplicate(\Drupal::request(), '/' . $path);
  $request->attributes->set('_system_path', $path);

  // We indicate that a menu administrator is running the menu access check.
  $request->attributes->set('_menu_admin', TRUE);

  // Attempt to match this path to provide a fully built request to the
  // access checker.
  try {
    $request->attributes->add(\Drupal::service('router')->matchRequest($request));
  }
  catch (ParamNotConvertedException $e) {
    return FALSE;
  }

  // Consult the accsss manager.
  $routes = $collection->all();
  $route = reset($routes);
  return \Drupal::service('access_manager')->check($route, $request, \Drupal::currentUser());
}
