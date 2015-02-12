<?php

/**
 * @file
 * Contains \Drupal\Core\Theme\ActiveTheme.
 */

namespace Drupal\Core\Theme;

/**
 * Defines a theme and its information needed at runtime.
 *
 * The theme manager will store the active theme object.
 *
 * @see \Drupal\Core\Theme\ThemeManager
 * @see \Drupal\Core\Theme\ThemeInitialization
 */
class ActiveTheme {

  /**
   * The machine name of the active theme.
   *
   * @var string
   */
  protected $name;

  /**
   * The path to the theme.
   *
   * @var string
   */
  protected $path;

  /**
   * The engine of the theme.
   *
   * @var string
   */
  protected $engine;

  /**
   * The path to the theme engine for root themes.
   *
   * @var string
   */
  protected $owner;

  /**
   * An array of base theme active theme objects keyed by name.
   *
   * @var static[]
   */
  protected $baseThemes;

  /**
   * The extension object.
   *
   * @var \Drupal\Core\Extension\Extension
   */
  protected $extension;

  /**
   * The stylesheets which are set to be removed by the theme.
   *
   * @var array
   */
  protected $styleSheetsRemove;

  /**
   * The stylesheets which are overridden by the theme.
   *
   * @var array
   */
  protected $styleSheetsOverride;

  /**
   * The libraries provided by the theme.
   *
   * @var array
   */
  protected $libraries;

  /**
   * Constructs an ActiveTheme object.
   *
   * @param array $values
   *   The properties of the object, keyed by the names.
   */
  public function __construct(array $values) {
    $this->name = $values['name'];
    $this->path = $values['path'];
    $this->engine = $values['engine'];
    $this->owner = $values['owner'];
    $this->styleSheetsRemove = $values['stylesheets_remove'];
    $this->styleSheetsOverride = $values['stylesheets_override'];
    $this->libraries = $values['libraries'];
    $this->extension = $values['extension'];
    $this->baseThemes = $values['base_themes'];
  }

  /**
   * Returns the machine name of the theme.
   *
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Returns the path to the theme directory.
   *
   * @return string
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * Returns the theme engine.
   *
   * @return string
   */
  public function getEngine() {
    return $this->engine;
  }

  /**
   * Returns the path to the theme engine for root themes.
   *
   * @see \Drupal\Core\Extension\ThemeHandler::rebuildThemeData
   *
   * @return mixed
   */
  public function getOwner() {
    return $this->owner;
  }

  /**
   * Returns the extension object.
   *
   * @return \Drupal\Core\Extension\Extension
   */
  public function getExtension() {
    return $this->extension;
  }

  /**
   * Returns the libraries provided by the theme.
   *
   * @return mixed
   */
  public function getLibraries() {
    return $this->libraries;
  }

  /**
   * Returns the overridden stylesheets by the theme.
   *
   * @return mixed
   */
  public function getStyleSheetsOverride() {
    return $this->styleSheetsOverride;
  }

  /**
   * Returns the removed stylesheets by the theme.
   *
   * @return mixed
   */
  public function getStyleSheetsRemove() {
    return $this->styleSheetsRemove;
  }

  /**
   * Returns an array of base theme active theme objects keyed by name.
   *
   * The order starts with the base theme of $this and ends with the root of
   * the dependency chain.
   *
   * @return static[]
   */
  public function getBaseThemes() {
    return $this->baseThemes;
  }

}
