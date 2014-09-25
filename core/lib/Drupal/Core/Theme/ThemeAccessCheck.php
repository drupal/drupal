<?php

/**
 * @file
 * Contains \Drupal\Core\Theme\ThemeAccessCheck.
 */

namespace Drupal\Core\Theme;

use Drupal\Core\Access\AccessResult;
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
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access($theme) {
    // Cacheable until the theme is modified.
    return AccessResult::allowedIf($this->checkAccess($theme))->addCacheTags(array('theme:' . $theme));
  }

  /**
   * Indicates whether the theme is accessible based on whether it is installed.
   *
   * @param string $theme
   *   The name of a theme.
   *
   * @return bool
   *   TRUE if the theme is installed, FALSE otherwise.
   */
  public function checkAccess($theme) {
    $themes = list_themes();
    return !empty($themes[$theme]->status);
  }

}
