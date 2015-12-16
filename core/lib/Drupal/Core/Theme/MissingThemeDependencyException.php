<?php

/**
 * @file
 * Contains \Drupal\Core\Theme\MissingThemeDependencyException.
 */

namespace Drupal\Core\Theme;

/**
 * Exception to be thrown when base theme for installed theme is not installed.
 *
 * @see \Drupal\Core\Theme\ThemeInitialization::getActiveThemeByName().
 */
class MissingThemeDependencyException extends \Exception {

  /**
   * The missing theme dependency.
   *
   * @var string
   */
  protected $theme;

  /**
   * Constructs the exception.
   *
   * @param string $message
   *   The exception message.
   * @param string $theme
   *   The missing theme dependency.
   */
  public function __construct($message, $theme) {
    parent::__construct($message);
    $this->theme = $theme;
  }

  /**
   * Gets the machine name of the missing theme.
   *
   * @return string
   *   The machine name of the theme that is missing.
   */
  public function getMissingThemeName() {
    return $this->theme;
  }

}
