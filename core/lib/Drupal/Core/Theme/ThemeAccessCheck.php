<?php

/**
 * @file
 * Contains \Drupal\Core\Theme\ThemeAccessCheck.
 */

namespace Drupal\Core\Theme;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Access check for a theme.
 */
class ThemeAccessCheck implements StaticAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_access_theme');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    return $this->checkAccess($request->attributes->get('theme')) ? static::ALLOW : static::DENY;
  }

  /**
   * Checks access to a theme.
   *
   * @param string $theme
   *   The name of a theme.
   *
   * @return bool
   *   TRUE if the theme is enabled, FALSE otherwise.
   */
  public function checkAccess($theme) {
    $themes = list_themes();
    return !empty($themes[$theme]->status);
  }

}
