<?php

/**
 * @file
 * Functions to handle paths in Drupal.
 */

use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Check if the current page is the front page.
 *
 * @return
 *   Boolean value: TRUE if the current page is the front page; FALSE if otherwise.
 *
 * @deprecated as of Drupal 8.0. Use
 *   \Drupal\Core\Path\PathMatcherInterface::isFrontPage() instead.
 */
function drupal_is_front_page() {
  return \Drupal::service('path.matcher')->isFrontPage();
}
