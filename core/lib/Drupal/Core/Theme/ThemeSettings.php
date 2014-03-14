<?php

/**
 * @file
 * Contains \Drupal\Core\Theme\ThemeSettings.
 */

namespace Drupal\Core\Theme;

use Drupal\Core\Config\ConfigBase;

/**
 * Provides a configuration API wrapper for runtime merged theme settings.
 *
 * Theme settings use configuration for base values but the runtime theme
 * settings are calculated based on various site settings and are therefore
 * not persisted.
 *
 * @see theme_get_setting()
 */
class ThemeSettings extends ConfigBase {

  /**
   * The theme of the theme settings object.
   *
   * @var string
   */
  protected $theme;

  /**
   * Constructs a theme settings object.
   *
   * @param string $theme
   *   The name of the theme settings object being constructed.
   */
  public function __construct($theme) {
    $this->theme = $theme;
  }

  /**
   * Returns the theme of this theme settings object.
   *
   * @return string
   *   The theme of this theme settings object.
   */
  public function getTheme() {
    return $this->theme;
  }
}
