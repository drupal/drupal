<?php

/**
 * @file
 * Contains \Drupal\Core\Theme\ThemeInitialization.
 */

namespace Drupal\Core\Theme;

use Drupal\Component\Utility\String;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\State\StateInterface;

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
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * Constructs a new ThemeInitialization object.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   */
  public function __construct($root, ThemeHandlerInterface $theme_handler, StateInterface $state) {
    $this->root = $root;
    $this->themeHandler = $theme_handler;
    $this->state = $state;
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
    if ($active_theme = $this->state->get('theme.active_theme.' . $theme_name)) {
      return $active_theme;
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
    $base_themes = array();
    $ancestor = $theme_name;
    while ($ancestor && isset($themes[$ancestor]->base_theme)) {
      $ancestor = $themes[$ancestor]->base_theme;
      $base_themes[] = $themes[$ancestor];
    }

    $active_theme = $this->getActiveTheme($themes[$theme_name], $base_themes);

    $this->state->set('theme.active_theme.' . $theme_name, $active_theme);
    return $active_theme;
  }

  /**
   * {@inheritdoc}
   */
  public function loadActiveTheme(ActiveTheme $active_theme) {
    // Initialize the theme.
    if ($theme_engine = $active_theme->getEngine()) {
      // Include the engine.
      include_once $this->root . '/' . $active_theme->getOwner();

      if (function_exists($theme_engine . '_init')) {
        foreach ($active_theme->getBaseThemes() as $base) {
          call_user_func($theme_engine . '_init', $base->getExtension());
        }
        call_user_func($theme_engine . '_init', $active_theme->getExtension());
      }
    }
    else {
      // include non-engine theme files
      foreach ($active_theme->getBaseThemes() as $base) {
        // Include the theme file or the engine.
        if ($base->getOwner()) {
          include_once $this->root . '/' . $base->getOwner();
        }
      }
      // and our theme gets one too.
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

    // Prepare stylesheets from this theme as well as all ancestor themes.
    // We work it this way so that we can have child themes override parent
    // theme stylesheets easily.
    $values['stylesheets'] = array();
    // CSS file basenames to override, pointing to the final, overridden filepath.
    $values['stylesheets_override'] = array();
    // CSS file basenames to remove.
    $values['stylesheets_remove'] = array();

    // Grab stylesheets from base theme.
    $final_stylesheets = array();
    foreach ($base_themes as $base) {
      if (!empty($base->stylesheets)) {
        foreach ($base->stylesheets as $media => $stylesheets) {
          foreach ($stylesheets as $name => $stylesheet) {
            $final_stylesheets[$media][$name] = $stylesheet;
          }
        }
      }
      $base_theme_path = $base->getPath();
      if (!empty($base->info['stylesheets-remove'])) {
        foreach ($base->info['stylesheets-remove'] as $basename) {
          $values['stylesheets_remove'][$basename] = $base_theme_path . '/' . $basename;
        }
      }
      if (!empty($base->info['stylesheets-override'])) {
        foreach ($base->info['stylesheets-override'] as $name) {
          $basename = drupal_basename($name);
          $values['stylesheets_override'][$basename] = $base_theme_path . '/' . $name;
        }
      }
    }

    // Add stylesheets used by this theme.
    if (!empty($theme->stylesheets)) {
      foreach ($theme->stylesheets as $media => $stylesheets) {
        foreach ($stylesheets as $name => $stylesheet) {
          $final_stylesheets[$media][$name] = $stylesheet;
        }
      }
    }
    if (!empty($theme->info['stylesheets-remove'])) {
      foreach ($theme->info['stylesheets-remove'] as $basename) {
        $values['stylesheets_remove'][$basename] = $theme_path . '/' . $basename;

        if (isset($values['stylesheets_override'][$basename])) {
          unset($values['stylesheets_override'][$basename]);
        }
      }
    }
    if (!empty($theme->info['stylesheets-override'])) {
      foreach ($theme->info['stylesheets-override'] as $name) {
        $basename = drupal_basename($name);
        $values['stylesheets_override'][$basename] = $theme_path . '/' . $name;

        if (isset($values['stylesheets_remove'][$basename])) {
          unset($values['stylesheets_remove'][$basename]);
        }
      }
    }

    // And now add the stylesheets properly.
    $values['stylesheets'] = $final_stylesheets;

    // Do basically the same as the above for libraries
    $values['libraries'] = array();

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

    $values['engine'] = isset($theme->engine) ? $theme->engine : NULL;
    $values['owner'] = isset($theme->owner) ? $theme->owner : NULL;
    $values['extension'] = $theme;

    $base_active_themes = array();
    foreach ($base_themes as $base_theme) {
      $base_active_themes[$base_theme->getName()] = $this->getActiveTheme($base_theme, array_slice($base_themes, 1));
    }

    $values['base_themes'] = $base_active_themes;

    return new ActiveTheme($values);
  }

}
