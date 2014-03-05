<?php

/**
 * @file
 * Functions to handle paths in Drupal.
 */

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Routing\RequestHelper;

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
 */
function drupal_match_path($path, $patterns) {
  $regexps = &drupal_static(__FUNCTION__);

  if (!isset($regexps[$patterns])) {
    // Convert path settings to a regular expression.
    // Therefore replace newlines with a logical or, /* with asterisks and the <front> with the frontpage.
    $to_replace = array(
      '/(\r\n?|\n)/', // newlines
      '/\\\\\*/',     // asterisks
      '/(^|\|)\\\\<front\\\\>($|\|)/' // <front>
    );
    $replacements = array(
      '|',
      '.*',
      '\1' . preg_quote(\Drupal::config('system.site')->get('page.front'), '/') . '\2'
    );
    $patterns_quoted = preg_quote($patterns, '/');
    $regexps[$patterns] = '/^(' . preg_replace($to_replace, $replacements, $patterns_quoted) . ')$/';
  }
  return (bool)preg_match($regexps[$patterns], $path);
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
 * This function is available only after DRUPAL_BOOTSTRAP_FULL.
 *
 * @return
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
 * Fetches a specific URL alias from the database.
 *
 * @param $conditions
 *   A string representing the source, a number representing the pid, or an
 *   array of query conditions.
 *
 * @see \Drupal\Core\Path\Path::load()
 */
function path_load($conditions) {
  if (is_numeric($conditions)) {
    $conditions = array('pid' => $conditions);
  }
  elseif (is_string($conditions)) {
    $conditions = array('source' => $conditions);
  }
  elseif (!is_array($conditions)) {
    return FALSE;
  }
  return \Drupal::service('path.crud')->load($conditions);
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
 * @see path_get_admin_paths()
 * @see hook_admin_paths()
 * @see hook_admin_paths_alter()
 */
function path_is_admin($path) {
  $path_map = &drupal_static(__FUNCTION__);
  if (!isset($path_map['admin'][$path])) {
    $patterns = path_get_admin_paths();
    $path_map['admin'][$path] = drupal_match_path($path, $patterns['admin']);
    $path_map['non_admin'][$path] = drupal_match_path($path, $patterns['non_admin']);
  }
  return $path_map['admin'][$path] && !$path_map['non_admin'][$path];
}

/**
 * Gets a list of administrative and non-administrative paths.
 *
 * @return array
 *   An associative array containing the following keys:
 *   'admin': An array of administrative paths and regular expressions
 *            in a format suitable for drupal_match_path().
 *   'non_admin': An array of non-administrative paths and regular expressions.
 *
 * @see hook_admin_paths()
 * @see hook_admin_paths_alter()
 */
function path_get_admin_paths() {
  $patterns = &drupal_static(__FUNCTION__);
  if (!isset($patterns)) {
    $paths = \Drupal::moduleHandler()->invokeAll('admin_paths');
    \Drupal::moduleHandler()->alter('admin_paths', $paths);
    // Combine all admin paths into one array, and likewise for non-admin paths,
    // for easier handling.
    $patterns = array();
    $patterns['admin'] = array();
    $patterns['non_admin'] = array();
    foreach ($paths as $path => $enabled) {
      if ($enabled) {
        $patterns['admin'][] = $path;
      }
      else {
        $patterns['non_admin'][] = $path;
      }
    }
    $patterns['admin'] = implode("\n", $patterns['admin']);
    $patterns['non_admin'] = implode("\n", $patterns['non_admin']);
  }
  return $patterns;
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
