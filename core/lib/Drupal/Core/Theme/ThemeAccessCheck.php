<?php

namespace Drupal\Core\Theme;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Provides access checking for themes for routing and theme negotiation.
 */
class ThemeAccessCheck implements AccessInterface {

  public function __construct(protected array $themes) {
  }

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
    // Cacheable until the theme settings are modified.
    return AccessResult::allowedIf($this->checkAccess($theme))->addCacheTags(['config:' . $theme . '.settings']);
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
    return isset($this->themes[$theme]);
  }

}
