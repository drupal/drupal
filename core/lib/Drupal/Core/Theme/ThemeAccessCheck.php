<?php

/**
 * @file
 * Contains \Drupal\Core\Theme\ThemeAccessCheck.
 */

namespace Drupal\Core\Theme;

use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Provides access checking for themes for routing and theme negotiation.
 */
class ThemeAccessCheck implements AccessInterface {

  /**
   * Checks access to the theme for routing.
   *
   * @param string $theme
   *   The name of a theme.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access($theme) {
    return $this->checkAccess($theme) ? static::ALLOW : static::DENY;
  }

  /**
   * Indicates whether the theme is accessible based on whether it is enabled.
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
