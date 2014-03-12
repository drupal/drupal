<?php

/**
 * @file
 * Contains \Drupal\Core\Theme\ThemeSettings.
 */

namespace Drupal\Core\Theme;

use Drupal\Core\Config\ConfigBase;

/**
 * Defines the default theme settings object.
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
