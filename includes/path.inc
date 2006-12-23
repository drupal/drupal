<?php
// $Id: path.inc,v 1.13 2006/12/23 22:04:52 dries Exp $

/**
 * @file
 * Functions to handle paths in Drupal, including path aliasing.
 *
 * These functions are not loaded for cached pages, but modules that need
 * to use them in hook_init() or hook exit() can make them available, by
 * executing "drupal_bootstrap(DRUPAL_BOOTSTRAP_PATH);".
 */

/**
 * Initialize the $_GET['q'] variable to the proper normal path.
 */
function drupal_init_path() {
  if (!empty($_GET['q'])) {
    $_GET['q'] = drupal_get_normal_path(trim($_GET['q'], '/'));
  }
  else {
    $_GET['q'] = drupal_get_normal_path(variable_get('site_frontpage', 'node'));
  }
}

/**
 * Given an alias, return its Drupal system URL if one exists. Given a Drupal
 * system URL return one of its aliases if such a one exists. Otherwise,
 * return FALSE.
 *
 * @param $action
 *   One of the following values:
 *   - wipe: delete the alias cache.
 *   - alias: return an alias for a given Drupal system path (if one exists).
 *   - source: return the Drupal system URL for a path alias (if one exists).
 * @param $path
 *   The path to investigate for corresponding aliases or system URLs.
 *
 * @return
 *   Either a Drupal system path, an aliased path, or FALSE if no path was
 *   found.
 */
function drupal_lookup_path($action, $path = '') {
  // $map keys are Drupal paths and the values are the corresponding aliases
  static $map = array(), $no_src = array();
  static $count;

  // Use $count to avoid looking up paths in subsequent calls if there simply are no aliases
  if (!isset($count)) {
    $count = db_result(db_query('SELECT COUNT(pid) FROM {url_alias}'));
  }

  if ($action == 'wipe') {
    $map = array();
    $no_src = array();
  }
  elseif ($count > 0 && $path != '') {
    if ($action == 'alias') {
      if (isset($map[$path])) {
        return $map[$path];
      }
      $alias = db_result(db_query("SELECT dst FROM {url_alias} WHERE src = '%s'", $path));
      $map[$path] = $alias;
      return $alias;
    }
    // Check $no_src for this $path in case we've already determined that there
    // isn't a path that has this alias
    elseif ($action == 'source' && !isset($no_src[$path])) {
      // Look for the value $path within the cached $map
      if (!$src = array_search($path, $map)) {
        if ($src = db_result(db_query("SELECT src FROM {url_alias} WHERE dst = '%s'", $path))) {
          $map[$src] = $path;
        }
        else {
          // We can't record anything into $map because we do not have a valid
          // index and there is no need because we have not learned anything
          // about any Drupal path. Thus cache to $no_src.
          $no_src[$path] = TRUE;
        }
      }
      return $src;
    }
  }

  return FALSE;
}

/**
 * Given an internal Drupal path, return the alias set by the administrator.
 *
 * @param $path
 *   An internal Drupal path.
 *
 * @return
 *   An aliased path if one was found, or the original path if no alias was
 *   found.
 */
function drupal_get_path_alias($path) {
  $result = $path;
  if ($alias = drupal_lookup_path('alias', $path)) {
    $result = $alias;
  }
  if (function_exists('custom_url_rewrite')) {
    $result = custom_url_rewrite('alias', $result, $path);
  }
  return $result;
}

/**
 * Given a path alias, return the internal path it represents.
 *
 * @param $path
 *   A Drupal path alias.
 *
 * @return
 *   The internal path represented by the alias, or the original alias if no
 *   internal path was found.
 */
function drupal_get_normal_path($path) {
  $result = $path;
  if ($src = drupal_lookup_path('source', $path)) {
    $result = $src;
  }
  if (function_exists('custom_url_rewrite')) {
    $result = custom_url_rewrite('source', $result, $path);
  }
  return $result;
}

/**
 * Return a component of the current Drupal path.
 *
 * When viewing a page at the path "admin/content/types", for example, arg(0)
 * would return "admin", arg(1) would return "content", and arg(2) would return
 * "types".
 *
 * Avoid use of this function where possible, as resulting code is hard to read.
 * Instead, attempt to use named arguments in menu callback functions. See the
 * explanation in menu.inc for how to construct callbacks that take arguments.
 *
 * @param $index
 *   The index of the component, where each component is separated by a '/'
 *   (forward-slash), and where the first component has an index of 0 (zero).
 *
 * @return
 *   The component specified by $index, or FALSE if the specified component was
 *   not found.
 */
function arg($index) {
  static $arguments, $q;

  if (empty($arguments) || $q != $_GET['q']) {
    $arguments = explode('/', $_GET['q']);
    $q = $_GET['q'];
  }

  if (isset($arguments[$index])) {
    return $arguments[$index];
  }
}

/**
 * Get the title of the current page, for display on the page and in the title bar.
 *
 * @return
 *   The current page's title.
 */
function drupal_get_title() {
  $title = drupal_set_title();

  // during a bootstrap, menu.inc is not included and thus we cannot provide a title
  if (!isset($title) && function_exists('menu_get_active_title')) {
    $title = check_plain(menu_get_active_title());
  }

  return $title;
}

/**
 * Set the title of the current page, for display on the page and in the title bar.
 *
 * @param $title
 *   Optional string value to assign to the page title; or if set to NULL
 *   (default), leaves the current title unchanged.
 *
 * @return
 *   The updated title of the current page.
 */
function drupal_set_title($title = NULL) {
  static $stored_title;

  if (isset($title)) {
    $stored_title = $title;
  }
  return $stored_title;
}

/**
 * Check if the current page is the front page.
 *
 * @return
 *   Boolean value: TRUE if the current page is the front page; FALSE if otherwise.
 */
function drupal_is_front_page() {
  // As drupal_init_path updates $_GET['q'] with the 'site_frontpage' path,
  // we can check it against the 'site_frontpage' variable.
  return $_GET['q'] == drupal_get_normal_path(variable_get('site_frontpage', 'node'));
}
