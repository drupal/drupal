<?php

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
   * The path to the logo.
   *
   * @var string
   */
  protected $logo;

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
   * An array of base theme extension objects keyed by name.
   *
   * @var \Drupal\Core\Extension\Extension[]
   */
  protected $baseThemeExtensions = [];

  /**
   * The extension object.
   *
   * @var \Drupal\Core\Extension\Extension
   */
  protected $extension;

  /**
   * The libraries provided by the theme.
   *
   * @var array
   */
  protected $libraries;

  /**
   * The regions provided by the theme.
   *
   * @var array
   */
  protected $regions;

  /**
   * The libraries or library assets overridden by the theme.
   *
   * @var array
   */
  protected $librariesOverride;

  /**
   * The list of libraries-extend definitions.
   *
   * @var array
   */
  protected $librariesExtend;

  /**
   * Constructs an ActiveTheme object.
   *
   * @param array $values
   *   The properties of the object, keyed by the names.
   */
  public function __construct(array $values) {
    $values += [
      'path' => '',
      'engine' => 'twig',
      'owner' => 'twig',
      'logo' => '',
      'libraries' => [],
      'extension' => 'html.twig',
      'base_theme_extensions' => [],
      'regions' => [],
      'libraries_override' => [],
      'libraries_extend' => [],
    ];

    $this->name = $values['name'];
    $this->logo = $values['logo'];
    $this->path = $values['path'];
    $this->engine = $values['engine'];
    $this->owner = $values['owner'];
    $this->libraries = $values['libraries'];
    $this->extension = $values['extension'];
    $this->baseThemeExtensions = $values['base_theme_extensions'];
    $this->regions = $values['regions'];
    $this->librariesOverride = $values['libraries_override'];
    $this->librariesExtend = $values['libraries_extend'];
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
   * @see \Drupal\Core\Extension\ThemeExtensionList::doList()
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
   * Returns an array of base theme extension objects keyed by name.
   *
   * The order starts with the base theme of $this and ends with the root of
   * the dependency chain.
   *
   * @return \Drupal\Core\Extension\Extension[]
   */
  public function getBaseThemeExtensions() {
    return $this->baseThemeExtensions;
  }

  /**
   * Returns the logo provided by the theme.
   *
   * @return string
   *   The logo path.
   */
  public function getLogo() {
    return $this->logo;
  }

  /**
   * The regions used by the theme.
   *
   * @return string[]
   *   The list of region machine names supported by the theme.
   *
   * @see system_region_list()
   */
  public function getRegions() {
    return array_keys($this->regions);
  }

  /**
   * Returns the libraries or library assets overridden by the active theme.
   *
   * @return array
   *   The list of libraries overrides.
   */
  public function getLibrariesOverride() {
    return $this->librariesOverride;
  }

  /**
   * Returns the libraries extended by the active theme.
   *
   * @return array
   *   The list of libraries-extend definitions.
   */
  public function getLibrariesExtend() {
    return $this->librariesExtend;
  }

}
