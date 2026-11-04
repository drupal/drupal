<?php

namespace Drupal\Core\Theme;

/**
 * Exception to be thrown when base theme for installed theme is not installed.
 *
 * @see \Drupal\Core\Theme\ThemeInitialization::getActiveThemeByName().
 */
class MissingThemeDependencyException extends \Exception {

  /**
   * Constructs the exception.
   *
   * @param string $message
   *   The exception message.
   * @param string $theme
   *   The missing theme dependency.
   */
  public function __construct(
    $message,
    protected $theme,
  ) {
    parent::__construct($message);
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
