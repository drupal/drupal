<?php

/**
 * @file
 * Theming for maintenance pages.
 */

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Site\Settings;

/**
 * Sets up the theming system for maintenance page.
 *
 * Used for site installs, updates and when the site is in maintenance mode.
 * It also applies when the database is unavailable or bootstrap was not
 * complete. Seven is always used for the initial install and update
 * operations. In other cases, Bartik is used, but this can be overridden by
 * setting a "maintenance_theme" key in the $settings variable in settings.php.
 */
function _drupal_maintenance_theme() {
  // If the theme is already set, assume the others are set too, and do nothing.
  if (\Drupal::theme()->hasActiveTheme()) {
    return;
  }

  require_once __DIR__ . '/path.inc';
  require_once __DIR__ . '/theme.inc';
  require_once __DIR__ . '/common.inc';
  require_once __DIR__ . '/unicode.inc';
  require_once __DIR__ . '/file.inc';
  require_once __DIR__ . '/module.inc';
  require_once __DIR__ . '/database.inc';
  Unicode::check();

  // Install and update pages are treated differently to prevent theming overrides.
  if (defined('MAINTENANCE_MODE') && (MAINTENANCE_MODE == 'install' || MAINTENANCE_MODE == 'update')) {
    if (drupal_installation_attempted()) {
      $custom_theme = $GLOBALS['install_state']['theme'];
    }
    else {
      $custom_theme = Settings::get('maintenance_theme', 'seven');
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
      // a safe default. Bartik is reasonably user friendly and fairly generic.
      $custom_theme = 'bartik';
    }
  }

  // Ensure that system.module is loaded.
  if (!function_exists('system_rebuild_theme_data')) {
    $module_handler = \Drupal::moduleHandler();
    $module_handler->addModule('system', 'core/modules/system');
    $module_handler->load('system');
  }

  $themes = list_themes();

  // If no themes are installed yet, or if the requested custom theme is not
  // installed, retrieve all available themes.
  /** @var \Drupal\Core\Theme\ThemeInitialization $theme_init */
  $theme_init = \Drupal::service('theme.initialization');
  if (empty($themes) || !isset($themes[$custom_theme])) {
    $theme_handler = \Drupal::service('theme_handler');
    $themes = $theme_handler->rebuildThemeData();
    $theme_handler->addTheme($themes[$custom_theme]);
    \Drupal::theme()->setActiveTheme($theme_init->getActiveTheme($themes[$custom_theme], array()));
  }

  // list_themes() triggers a \Drupal\Core\Extension\ModuleHandler::alter() in
  // maintenance mode, but we can't let themes alter the .info.yml data until
  // we know a theme's base themes. So don't set active theme until after
  // list_themes() builds its cache.
  $theme = $custom_theme;

  // Find all our ancestor themes and put them in an array.
  $base_theme = array();
  $ancestor = $theme;
  while ($ancestor && isset($themes[$ancestor]->base_theme)) {
    $base_theme[] = $new_base_theme = $themes[$themes[$ancestor]->base_theme];
    $ancestor = $themes[$ancestor]->base_theme;
  }
  // @todo This is just a workaround. Find a better way how to handle themes
  //   on maintenance pages, see https://www.drupal.org/node/2322619.
  \Drupal::theme()->setActiveTheme($theme_init->getActiveTheme($themes[$custom_theme], array_reverse($base_theme)));
  // Prime the theme registry.
  Drupal::service('theme.registry');
}

/**
 * Returns HTML for a results report of an operation run by authorize.php.
 *
 * @param $variables
 *   An associative array containing:
 *   - messages: An array of result messages.
 *
 * @ingroup themeable
 */
function theme_authorize_report($variables) {
  $messages = $variables['messages'];
  $output = '';
  if (!empty($messages)) {
    $output .= '<div class="authorize-results">';
    foreach ($messages as $heading => $logs) {
      $items = array();
      foreach ($logs as $number => $log_message) {
        if ($number === '#abort') {
          continue;
        }
        $authorize_message = array(
          '#theme' => 'authorize_message',
          '#message' => $log_message['message'],
          '#success' => $log_message['success'],
        );
        $items[] = drupal_render($authorize_message);
      }
      $item_list = array(
        '#theme' => 'item_list',
        '#items' => $items,
        '#title' => $heading,
      );
      $output .= drupal_render($item_list);
    }
    $output .= '</div>';
  }
  return $output;
}

/**
 * Returns HTML for a single log message from the authorize.php batch operation.
 *
 * @param $variables
 *   An associative array containing:
 *   - message: The log message.
 *     It's the caller's responsibility to ensure this string contains no
 *     dangerous HTML such as SCRIPT tags.
 *   - success: A boolean indicating failure or success.
 *
 * @ingroup themeable
 */
function theme_authorize_message($variables) {
  $message = $variables['message'];
  $success = $variables['success'];
  if ($success) {
    $item = array('data' => $message, 'class' => array('authorize-results__success'));
  }
  else {
    $item = array('data' => $message, 'class' => array('authorize-results__failure'));
  }
  return $item;
}
