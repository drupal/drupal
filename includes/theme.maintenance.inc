<?php
// $Id: theme.maintenance.inc,v 1.55 2010/03/17 14:04:38 dries Exp $

/**
 * @file
 * Theming for maintenance pages.
 */

/**
 * Sets up the theming system for site installs, updates and when the site is
 * in maintenance mode. It also applies when the database is unavailable.
 *
 * Seven is always used for the initial install and update operations. In
 * other cases, Garland is used, but this can be overridden by setting a
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
    if (!db_is_active()) {
      // Because we are operating in a crippled environment, we need to
      // bootstrap just enough to allow hook invocations to work.
      require_once DRUPAL_ROOT . '/includes/database/database.inc';
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
 * Return a themed list of maintenance tasks to perform.
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
 * Generate a themed installation page.
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
  // Variable processors invoked manually since this function and theme_update_page()
  // are exceptions in how it works within the theme system.
  template_preprocess($variables, 'install_page');
  template_preprocess_maintenance_page($variables);
  template_process($variables, 'install_page');
  template_process_maintenance_page($variables);

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

  // This was called as a theme hook (not template), so we need to fix
  // path_to_theme() for the template, to point at the actual theme rather than
  // system module as owner of the hook. Additionally, figure out the
  // maintenance page template to use.
  global $theme_path, $theme_info, $base_theme_info;
  $theme_path = dirname($theme_info->uri);
  $base_themes = $base_theme_info;
  // Make sure a maintenance-page.tpl.php is always found.
  $base_themes[] = 'modules/system';
  while (!file_exists($theme_path . '/maintenance-page.tpl.php') && $base_theme = array_shift($base_themes)) {
    $theme_path = dirname($base_theme->uri);
  }

  return theme_render_template($theme_path . '/maintenance-page.tpl.php', $variables);
}

/**
 * Generate a themed update page.
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

  // Variable processors invoked manually since this function and theme_install_page()
  // are exceptions in how it works within the theme system.
  template_preprocess($variables, 'update_page');
  template_preprocess_maintenance_page($variables);
  template_process($variables, 'update_page');
  template_process_maintenance_page($variables);

  // Special handling of warning messages.
  $messages = drupal_set_message();
  if (isset($messages['warning'])) {
    $title = count($messages['warning']) > 1 ? 'The following update warnings should be carefully reviewed before continuing' : 'The following update warning should be carefully reviewed before continuing';
    $variables['messages'] .= '<h4>' . $title . ':</h4>';
    $variables['messages'] .= theme('status_messages', array('display' => 'warning'));
  }

  // This was called as a theme hook (not template), so we need to fix
  // path_to_theme() for the template, to point at the actual theme rather than
  // system module as owner of the hook. Additionally, figure out the
  // maintenance page template to use.
  global $theme_path, $theme_info, $base_theme_info;
  $theme_path = dirname($theme_info->uri);
  $base_themes = $base_theme_info;
  // Make sure a maintenance-page.tpl.php is always found.
  $base_themes[] = 'modules/system';
  while (!file_exists($theme_path . '/maintenance-page.tpl.php') && $base_theme = array_shift($base_themes)) {
    $theme_path = dirname($base_theme->uri);
  }

  return theme_render_template($theme_path . '/maintenance-page.tpl.php', $variables);
}

/**
 * Generate a report of the results from an operation run via authorize.php.
 *
 * @param array $variables
 *   - messages: An array of result messages.
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
 * Render a single log message from the authorize.php batch operation.
 *
 * @param $variables
 *   - message: The log message.
 *   - success: A boolean indicating failure or success.
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
