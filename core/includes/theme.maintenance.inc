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
  global $theme, $theme_key;

  // If $theme is already set, assume the others are set too, and do nothing.
  if (isset($theme)) {
    return;
  }

  require_once DRUPAL_ROOT . '/' . Settings::get('path_inc', 'core/includes/path.inc');
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
  if (empty($themes) || !isset($themes[$custom_theme])) {
    $theme_handler = \Drupal::service('theme_handler');
    $themes = $theme_handler->rebuildThemeData();
    $theme_handler->addTheme($themes[$custom_theme]);
  }

  // list_themes() triggers a \Drupal\Core\Extension\ModuleHandler::alter() in
  // maintenance mode, but we can't let themes alter the .info.yml data until
  // we know a theme's base themes. So don't set global $theme until after
  // list_themes() builds its cache.
  $theme = $custom_theme;

  // Store the identifier for retrieving theme settings with.
  $theme_key = $theme;

  // Find all our ancestor themes and put them in an array.
  $base_theme = array();
  $ancestor = $theme;
  while ($ancestor && isset($themes[$ancestor]->base_theme)) {
    $base_theme[] = $new_base_theme = $themes[$themes[$ancestor]->base_theme];
    $ancestor = $themes[$ancestor]->base_theme;
  }
  _drupal_theme_initialize($themes[$theme], array_reverse($base_theme));
  // Prime the theme registry.
  // @todo Remove global theme variables.
  Drupal::service('theme.registry');
}

/**
 * Returns HTML for a list of maintenance tasks to perform.
 *
 * @param $variables
 *   An associative array containing:
 *   - items: An associative array of maintenance tasks.
 *     It's the caller's responsibility to ensure this array's items contain no
 *     dangerous HTML such as SCRIPT tags.
 *   - active: The key for the currently active maintenance task.
 *
 * @ingroup themeable
 */
function theme_task_list($variables) {
  $items = $variables['items'];
  $active = $variables['active'];

  $done = isset($items[$active]) || $active == NULL;
  $output = '<h2 class="visually-hidden">Installation tasks</h2>';
  $output .= '<ol class="task-list">';

  foreach ($items as $k => $item) {
    if ($active == $k) {
      $class = 'active';
      $status = '(' . t('active') . ')';
      $done = FALSE;
    }
    else {
      $class = $done ? 'done' : '';
      $status = $done ? '(' . t('done') . ')' : '';
    }
    $output .= '<li';
    $output .= ($class ? ' class="' . $class . '"' : '') . '>';
    $output .= $item;
    $output .= ($status ? '<span class="visually-hidden"> ' . $status . '</span>' : '');
    $output .= '</li>';
  }
  $output .= '</ol>';
  return $output;
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
    $output .= '<div id="authorize-results">';
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
    $item = array('data' => $message, 'class' => array('success'));
  }
  else {
    $item = array('data' => '<strong>' . $message . '</strong>', 'class' => array('failure'));
  }
  return $item;
}
