<?php
// $Id: theme.maintenance.inc,v 1.59 2010/05/12 09:22:24 dries Exp $

/**
 * @file
 * Theming for maintenance pages.
 */

/**
 * Sets up the theming system for maintenance page.
 *
 * Used for site installs, updates and when the site is in maintenance mode.
 * It also applies when the database is unavailable or bootstrap was not
 * complete. Seven is always used for the initial install and update operations.
 * In other cases, Garland is used, but this can be overridden by setting a
 * "maintenance_theme" key in the $conf variable in settings.php.
 */
function _drupal_maintenance_theme() {
  global $theme, $theme_key, $conf;

  // If $theme is already set, assume the others are set too, and do nothing.
  if (isset($theme)) {
    return;
  }

  require_once DRUPAL_ROOT . '/' . variable_get('path_inc', 'includes/path.inc');
  require_once DRUPAL_ROOT . '/includes/theme.inc';
  require_once DRUPAL_ROOT . '/includes/common.inc';
  require_once DRUPAL_ROOT . '/includes/unicode.inc';
  require_once DRUPAL_ROOT . '/includes/file.inc';
  require_once DRUPAL_ROOT . '/includes/module.inc';
  unicode_check();

  // Install and update pages are treated differently to prevent theming overrides.
  if (defined('MAINTENANCE_MODE') && (MAINTENANCE_MODE == 'install' || MAINTENANCE_MODE == 'update')) {
    $custom_theme = (isset($conf['maintenance_theme']) ? $conf['maintenance_theme'] : 'seven');
  }
  else {
    // The bootstrap was not complete. So we are operating in a crippled
    // environment, we need to bootstrap just enough to allow hook invocations
    // to work. See _drupal_log_error().
    if (!class_exists('Database', FALSE)) {
      require_once DRUPAL_ROOT . '/includes/database/database.inc';
      spl_autoload_register('db_autoload');
    }

    // Ensure that system.module is loaded.
    if (!function_exists('_system_rebuild_theme_data')) {
      $module_list['system']['filename'] = 'modules/system/system.module';
      module_list(TRUE, FALSE, FALSE, $module_list);
      drupal_load('module', 'system');
    }

    // We use the default theme as the maintenance theme. If a default theme
    // isn't specified in the database or in settings.php, we use Garland.
    $custom_theme = variable_get('maintenance_theme', variable_get('theme_default', 'garland'));
  }

  $themes = list_themes();

  // list_themes() triggers a drupal_alter() in maintenance mode, but we can't
  // let themes alter the .info data until we know a theme's base themes. So
  // don't set global $theme until after list_themes() builds its cache.
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
  _drupal_theme_initialize($themes[$theme], array_reverse($base_theme), '_theme_load_offline_registry');

  // These are usually added from system_init() -except maintenance.css.
  // When the database is inactive it's not called so we add it here.
  drupal_add_css(drupal_get_path('module', 'system') . '/system.css');
  drupal_add_css(drupal_get_path('module', 'system') . '/system-behavior.css');
  drupal_add_css(drupal_get_path('module', 'system') . '/system-menus.css');
  drupal_add_css(drupal_get_path('module', 'system') . '/maintenance.css');
  drupal_add_css(drupal_get_path('module', 'system') . '/admin.css');
}

/**
 * This builds the registry when the site needs to bypass any database calls.
 */
function _theme_load_offline_registry($theme, $base_theme = NULL, $theme_engine = NULL) {
  $registry = _theme_build_registry($theme, $base_theme, $theme_engine);
  _theme_set_registry($registry);
}

/**
 * Returns HTML for a list of maintenance tasks to perform.
 *
 * @param $variables
 *   An associative array containing:
 *   - items: An associative array of maintenance tasks.
 *   - active: The key for the currently active maintenance task.
 *
 * @ingroup themeable
 */
function theme_task_list($variables) {
  $items = $variables['items'];
  $active = $variables['active'];

  $done = isset($items[$active]) || $active == NULL;
  $output = '<h2 class="element-invisible">Installation tasks</h2>';
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
    $output .= ($status ? '<span class="element-invisible">' . $status . '</span>' : '');
    $output .= '</li>';
  }
  $output .= '</ol>';
  return $output;
}

/**
 * Returns HTML for the installation page.
 *
 * Note: this function is not themeable.
 *
 * @param $variables
 *   An associative array containing:
 *   - content: The page content to show.
 */
function theme_install_page($variables) {
  drupal_add_http_header('Content-Type', 'text/html; charset=utf-8');

  // Delay setting the message variable so it can be processed below.
  $variables['show_messages'] = FALSE;

  // Special handling of error messages
  $messages = drupal_set_message();
  if (isset($messages['error'])) {
    $title = count($messages['error']) > 1 ? st('The following errors must be resolved before you can continue the installation process') : st('The following error must be resolved before you can continue the installation process');
    $variables['messages'] .= '<h3>' . $title . ':</h3>';
    $variables['messages'] .= theme('status_messages', array('display' => 'error'));
    $variables['content'] .= '<p>' . st('Check the error messages and <a href="!url">try again</a>.', array('!url' => check_url(request_uri()))) . '</p>';
  }

  // Special handling of warning messages
  if (isset($messages['warning'])) {
    $title = count($messages['warning']) > 1 ? st('The following installation warnings should be carefully reviewed') : st('The following installation warning should be carefully reviewed');
    $variables['messages'] .= '<h4>' . $title . ':</h4>';
    $variables['messages'] .= theme('status_messages', array('display' => 'warning'));
  }

  // Special handling of status messages
  if (isset($messages['status'])) {
    $title = count($messages['status']) > 1 ? st('The following installation warnings should be carefully reviewed, but in most cases may be safely ignored') : st('The following installation warning should be carefully reviewed, but in most cases may be safely ignored');
    $variables['messages'] .= '<h4>' . $title . ':</h4>';
    $variables['messages'] .= theme('status_messages', array('display' => 'status'));
  }

  return theme('maintenance_page', $variables);
}

/**
 * Returns HTML for the update page.
 *
 * Note: this function is not themeable.
 *
 * @param $variables
 *   An associative array containing:
 *   - content: The page content to show.
 *   - show_messages: Whether to output status and error messages.
 *     FALSE can be useful to postpone the messages to a subsequent page.
 */
function theme_update_page($variables) {
  // Set required headers.
  drupal_add_http_header('Content-Type', 'text/html; charset=utf-8');

  // Special handling of warning messages.
  $messages = drupal_set_message();
  if (isset($messages['warning'])) {
    $title = count($messages['warning']) > 1 ? 'The following update warnings should be carefully reviewed before continuing' : 'The following update warning should be carefully reviewed before continuing';
    $variables['messages'] .= '<h4>' . $title . ':</h4>';
    $variables['messages'] .= theme('status_messages', array('display' => 'warning'));
  }

  return theme('maintenance_page', $variables);
}

/**
 * Returns HTML for a report of the results from an operation run via authorize.php.
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
      $output .= '<h3>' . check_plain($heading) . '</h3>';
      foreach ($logs as $number => $log_message) {
        if ($number === '#abort') {
          continue;
        }
        $output .= theme('authorize_message', array('message' => $log_message['message'], 'success' => $log_message['success']));
      }
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
 *   - success: A boolean indicating failure or success.
 *
 * @ingroup themeable
 */
function theme_authorize_message($variables) {
  $output = '';
  $message = $variables['message'];
  $success = $variables['success'];
  if ($success) {
    $output .= '<li class="success">' . $message . '</li>';
  }
  else {
    $output .= '<li class="failure"><strong>' . t('Failed') . ':</strong> ' . $message . '</li>';
  }
  return $output;
}
