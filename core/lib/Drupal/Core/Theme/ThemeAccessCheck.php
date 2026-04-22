<?php

namespace Drupal\Core\Theme;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Provides access checking for themes for routing and theme negotiation.
 */
class ThemeAccessCheck implements AccessInterface {

  /**
   * Constructs a \Drupal\Core\Theme\Registry object.
   *
   * @param array $themes
   *   Theme information from the container parameter.
   */
  public function __construct(protected $themes) {
    if (!is_array($themes)) {
      @trigger_error('Passing ThemeHandlerInterface to ' . __METHOD__ . ' is deprecated in drupal::11.4.0 and is removed from drupal:12.0.0. Pass theme info from the "container.themes" container parameter instead. See https://www.drupal.org/project/drupal/issues/2538970');
      $this->themes = \Drupal::getContainer()->getParameter('container.themes');
    }
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
