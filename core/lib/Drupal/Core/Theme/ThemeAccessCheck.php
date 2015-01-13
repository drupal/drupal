<?php

/**
 * @file
 * Contains \Drupal\Core\Theme\ThemeAccessCheck.
 */

namespace Drupal\Core\Theme;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Provides access checking for themes for routing and theme negotiation.
 */
class ThemeAccessCheck implements AccessInterface {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a \Drupal\Core\Theme\Registry object.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(ThemeHandlerInterface $theme_handler) {
    $this->themeHandler = $theme_handler;
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
    $themes = $this->themeHandler->listInfo();
    return !empty($themes[$theme]->status);
  }

}
