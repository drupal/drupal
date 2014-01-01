<?php

/**
 * @file
 * Contains \Drupal\Core\Theme\ThemeAccessCheck.
 */

namespace Drupal\Core\Theme;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Access check for a theme.
 */
class ThemeAccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
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
