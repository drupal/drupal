<?php

/**
 * @file
 * Theming for maintenance pages.
 */

use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Site\Settings;

/**
 * Sets up the theming system for maintenance page.
 *
 * Used for site installs, updates and when the site is in maintenance mode.
 * It also applies when the database is unavailable or bootstrap was not
 * complete. Seven is always used for the initial install and update
 * operations. In other cases, Claro is used, but this can be overridden by
 * setting a "maintenance_theme" key in the $settings variable in settings.php.
 */
function _drupal_maintenance_theme() {
  // If the theme is already set, assume the others are set too, and do nothing.
  if (\Drupal::theme()->hasActiveTheme()) {
    return;
  }

  require_once __DIR__ . '/theme.inc';
  require_once __DIR__ . '/common.inc';
  require_once __DIR__ . '/file.inc';
  require_once __DIR__ . '/module.inc';

  // Install and update pages are treated differently to prevent theming overrides.
  if (defined('MAINTENANCE_MODE') && (MAINTENANCE_MODE == 'install' || MAINTENANCE_MODE == 'update')) {
    if (InstallerKernel::installationAttempted()) {
      $custom_theme = $GLOBALS['install_state']['theme'];
    }
    else {
      $custom_theme = Settings::get('maintenance_theme', 'claro');
    }
  }
  else {
    // Use the maintenance theme if specified, otherwise attempt to use the
    // default site theme.
    try {
      $custom_theme = Settings::get('maintenance_theme', '');
      if (!$custom_theme) {
        $config = \Drupal::config('system.theme');
        $custom_theme = $config->get('default');
      }
    }
    catch (\Exception $e) {
      // Whatever went wrong (often a database connection problem), we are
      // about to fall back to a sensible theme so there is no need for special
      // handling.
    }
    if (!$custom_theme) {
      // We have been unable to identify the configured theme, so fall back to
      // a safe default. Claro is reasonably user friendly and fairly generic.
      $custom_theme = 'claro';
    }
  }

  $themes = \Drupal::service('theme_handler')->listInfo();

  // If no themes are installed yet, or if the requested custom theme is not
  // installed, retrieve all available themes.
  /** @var \Drupal\Core\Theme\ThemeInitialization $theme_init */
  $theme_init = \Drupal::service('theme.initialization');
  $theme_handler = \Drupal::service('theme_handler');
  if (empty($themes) || !isset($themes[$custom_theme])) {
    $themes = \Drupal::service('extension.list.theme')->getList();
    $theme_handler->addTheme($themes[$custom_theme]);
  }

  // \Drupal\Core\Extension\ThemeHandlerInterface::listInfo() triggers a
  // \Drupal\Core\Extension\ModuleHandler::alter() in maintenance mode, but we
  // can't let themes alter the .info.yml data until we know a theme's base
  // themes. So don't set active theme until after
  // \Drupal\Core\Extension\ThemeHandlerInterface::listInfo() builds its cache.
  $theme = $custom_theme;

  // Find all our ancestor themes and put them in an array.
  // @todo This is just a workaround. Find a better way how to handle themes
  //   on maintenance pages, see https://www.drupal.org/node/2322619.
  // This code is basically a duplicate of
  // \Drupal\Core\Theme\ThemeInitialization::getActiveThemeByName.
  $base_themes = [];
  $ancestor = $theme;
  while ($ancestor && isset($themes[$ancestor]->base_theme)) {
    $base_themes[] = $themes[$themes[$ancestor]->base_theme];
    $ancestor = $themes[$ancestor]->base_theme;
    if ($ancestor) {
      // Ensure that the base theme is added and installed.
      $theme_handler->addTheme($themes[$ancestor]);
    }
  }
  \Drupal::theme()->setActiveTheme($theme_init->getActiveTheme($themes[$custom_theme], $base_themes));
  // Prime the theme registry.
  Drupal::service('theme.registry');
}

/**
 * Prepares variables for authorize.php operation report templates.
 *
 * This report displays the results of an operation run via authorize.php.
 *
 * Default template: authorize-report.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - messages: An array of result messages.
 */
function template_preprocess_authorize_report(&$variables) {
  $messages = [];
  if (!empty($variables['messages'])) {
    foreach ($variables['messages'] as $heading => $logs) {
      $items = [];
      foreach ($logs as $number => $log_message) {
        if ($number === '#abort') {
          continue;
        }
        $class = 'authorize-results__' . ($log_message['success'] ? 'success' : 'failure');
        $items[] = [
          '#wrapper_attributes' => ['class' => [$class]],
          '#markup' => $log_message['message'],
        ];
      }
      $messages[] = [
        '#theme' => 'item_list',
        '#items' => $items,
        '#title' => $heading,
      ];
    }
  }
  $variables['messages'] = $messages;
}
