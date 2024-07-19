<?php

namespace Drupal\Core\Theme;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;

/**
 * Provides the theme initialization logic.
 */
class ThemeInitialization implements ThemeInitializationInterface {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The cache backend to use for the active theme.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The extensions that might be attaching assets.
   *
   * @var array
   */
  protected $extensions;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new ThemeInitialization object.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to use to load modules.
   */
  public function __construct($root, ThemeHandlerInterface $theme_handler, CacheBackendInterface $cache, ModuleHandlerInterface $module_handler) {
    $this->root = $root;
    $this->themeHandler = $theme_handler;
    $this->cache = $cache;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function initTheme($theme_name) {
    $active_theme = $this->getActiveThemeByName($theme_name);
    $this->loadActiveTheme($active_theme);

    return $active_theme;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveThemeByName($theme_name) {
    if ($cached = $this->cache->get('theme.active_theme.' . $theme_name)) {
      return $cached->data;
    }
    $themes = $this->themeHandler->listInfo();

    // If no theme could be negotiated, or if the negotiated theme is not within
    // the list of installed themes, fall back to the default theme output of
    // core and modules (like Stark, but without a theme extension at all). This
    // is possible, because loadActiveTheme() always loads the Twig theme
    // engine. This is desired, because missing or malformed theme configuration
    // should not leave the application in a broken state. By falling back to
    // default output, the user is able to reconfigure the theme through the UI.
    // Lastly, tests are expected to operate with no theme by default, so as to
    // only assert the original theme output of modules (unless a test manually
    // installs a specific theme).
    if (empty($themes) || !$theme_name || !isset($themes[$theme_name])) {
      $theme_name = 'core';
      // /core/core.info.yml does not actually exist, but is required because
      // Extension expects a pathname.
      $active_theme = $this->getActiveTheme(new Extension($this->root, 'theme', 'core/core.info.yml'));

      // Early-return and do not set state, because the initialized $theme_name
      // differs from the original $theme_name.
      return $active_theme;
    }

    // Find all our ancestor themes and put them in an array.
    $base_themes = [];
    $ancestor = $theme_name;
    while ($ancestor && isset($themes[$ancestor]->base_theme)) {
      $ancestor = $themes[$ancestor]->base_theme;
      if (!$this->themeHandler->themeExists($ancestor)) {
        throw new MissingThemeDependencyException(sprintf('Base theme %s has not been installed.', $ancestor), $ancestor);
      }
      $base_themes[] = $themes[$ancestor];
    }

    $active_theme = $this->getActiveTheme($themes[$theme_name], $base_themes);

    $this->cache->set('theme.active_theme.' . $theme_name, $active_theme);
    return $active_theme;
  }

  /**
   * {@inheritdoc}
   */
  public function loadActiveTheme(ActiveTheme $active_theme) {
    // Initialize the theme.
    if ($active_theme->getEngine()) {
      // Include the engine.
      include_once $this->root . '/' . $active_theme->getOwner();
      foreach (array_reverse($active_theme->getBaseThemeExtensions()) as $base) {
        $base->load();
      }
      $active_theme->getExtension()->load();
    }
    else {
      // Include non-engine theme files
      foreach (array_reverse($active_theme->getBaseThemeExtensions()) as $base) {
        // Include the theme file or the engine.
        if ($base->owner) {
          include_once $this->root . '/' . $base->owner;
        }
      }
      // And our theme gets one too.
      if ($active_theme->getOwner()) {
        include_once $this->root . '/' . $active_theme->getOwner();
      }
    }

    // Always include Twig as the default theme engine.
    include_once $this->root . '/core/themes/engines/twig/twig.engine';
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveTheme(Extension $theme, array $base_themes = []) {
    $theme_path = $theme->getPath();

    $values['path'] = $theme_path;
    $values['name'] = $theme->getName();

    // Use the logo declared in this themes info file, otherwise use logo.svg
    // from the themes root.
    if (!empty($theme->info['logo'])) {
      $values['logo'] = $theme->getPath() . '/' . $theme->info['logo'];
    }
    else {
      $values['logo'] = $theme->getPath() . '/logo.svg';
    }

    // Prepare libraries overrides from this theme and ancestor themes. This
    // allows child themes to easily remove CSS files from base themes and
    // modules.
    $values['libraries_override'] = [];

    // Get libraries overrides declared by base themes.
    foreach (array_reverse($base_themes) as $base) {
      if (!empty($base->info['libraries-override'])) {
        foreach ($base->info['libraries-override'] as $library => $override) {
          $values['libraries_override'][$base->getPath()][$library] = $override;
        }
      }
    }

    // Add libraries overrides declared by this theme.
    if (!empty($theme->info['libraries-override'])) {
      foreach ($theme->info['libraries-override'] as $library => $override) {
        $values['libraries_override'][$theme->getPath()][$library] = $override;
      }
    }

    // Get libraries extensions declared by base themes.
    foreach ($base_themes as $base) {
      if (!empty($base->info['libraries-extend'])) {
        foreach ($base->info['libraries-extend'] as $library => $extend) {
          if (isset($values['libraries_extend'][$library])) {
            // Merge if libraries-extend has already been defined for this
            // library.
            $values['libraries_extend'][$library] = array_merge($values['libraries_extend'][$library], $extend);
          }
          else {
            $values['libraries_extend'][$library] = $extend;
          }
        }
      }
    }
    // Add libraries extensions declared by this theme.
    if (!empty($theme->info['libraries-extend'])) {
      foreach ($theme->info['libraries-extend'] as $library => $extend) {
        if (isset($values['libraries_extend'][$library])) {
          // Merge if libraries-extend has already been defined for this
          // library.
          $values['libraries_extend'][$library] = array_merge($values['libraries_extend'][$library], $extend);
        }
        else {
          $values['libraries_extend'][$library] = $extend;
        }
      }
    }

    // Do basically the same as the above for libraries
    $values['libraries'] = [];

    // Grab libraries from base theme
    foreach ($base_themes as $base) {
      if (!empty($base->libraries)) {
        foreach ($base->libraries as $library) {
          $values['libraries'][] = $library;
        }
      }
    }

    // Add libraries used by this theme.
    if (!empty($theme->libraries)) {
      foreach ($theme->libraries as $library) {
        $values['libraries'][] = $library;
      }
    }

    $values['engine'] = $theme->engine ?? NULL;
    $values['owner'] = $theme->owner ?? NULL;
    $values['extension'] = $theme;

    $base_active_themes = [];
    foreach ($base_themes as $base_theme) {
      $base_active_themes[$base_theme->getName()] = $base_theme;
    }

    $values['base_theme_extensions'] = $base_active_themes;
    if (!empty($theme->info['regions'])) {
      $values['regions'] = $theme->info['regions'];
    }

    return new ActiveTheme($values);
  }

  /**
   * Gets all extensions.
   *
   * @return array
   */
  protected function getExtensions() {
    if (!isset($this->extensions)) {
      $this->extensions = array_merge($this->moduleHandler->getModuleList(), $this->themeHandler->listInfo());
    }
    return $this->extensions;
  }

  /**
   * Gets CSS file where tokens have been resolved.
   *
   * @param string $css_file
   *   CSS file which may contain tokens.
   *
   * @return string
   *   CSS file where placeholders are replaced.
   *
   * @todo Remove in Drupal 9.0.x.
   */
  protected function resolveStyleSheetPlaceholders($css_file) {
    $token_candidate = explode('/', $css_file)[0];
    if (!preg_match('/@[A-z0-9_-]+/', $token_candidate)) {
      return $css_file;
    }

    $token = substr($token_candidate, 1);

    // Prime extensions.
    $extensions = $this->getExtensions();
    if (isset($extensions[$token])) {
      return str_replace($token_candidate, $extensions[$token]->getPath(), $css_file);
    }
  }

}
