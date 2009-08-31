<?php
// $Id$

/**
 * @file
 * Theming for maintenance pages.
 */

/**
 * Sets up the theming system for site installs, updates and when the site is
 * in maintenance mode. It also applies when the database is unavailable.
 *
 * Minnelli is always used for the initial install and update operations. In
 * other cases, "settings.php" must have a "maintenance_theme" key set for the
 * $conf variable in order to change the maintenance theme.
 */
function _drupal_maintenance_theme() {
  global $theme, $theme_key;

  // If $theme is already set, assume the others are set too, and do nothing.
  if (isset($theme)) {
    return;
  }

  require_once DRUPAL_ROOT . '/includes/path.inc';
  require_once DRUPAL_ROOT . '/includes/theme.inc';
  require_once DRUPAL_ROOT . '/includes/common.inc';
  require_once DRUPAL_ROOT . '/includes/unicode.inc';
  require_once DRUPAL_ROOT . '/includes/file.inc';
  require_once DRUPAL_ROOT . '/includes/module.inc';
  require_once DRUPAL_ROOT . '/includes/database/database.inc';
  unicode_check();

  // Install and update pages are treated differently to prevent theming overrides.
  if (defined('MAINTENANCE_MODE') && (MAINTENANCE_MODE == 'install' || MAINTENANCE_MODE == 'update')) {
    $theme = 'minnelli';
  }
  else {
    if (!db_is_active()) {
      // Because we are operating in a crippled environment, we need to
      // bootstrap just enough to allow hook invocations to work.
      $module_list['system']['filename'] = 'modules/system/system.module';
      $module_list['filter']['filename'] = 'modules/filter/filter.module';
      module_list(TRUE, FALSE, FALSE, $module_list);
      drupal_load('module', 'system');
      drupal_load('module', 'filter');
    }

    $theme = variable_get('maintenance_theme', 'minnelli');
  }

  $themes = list_themes();

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
  drupal_add_css(drupal_get_path('module', 'system') . '/defaults.css');
  drupal_add_css(drupal_get_path('module', 'system') . '/system.css');
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
function theme_task_list($items, $active = NULL) {
  $done = isset($items[$active]) || $active == NULL;
  $output = '<ol class="task-list">';
  foreach ($items as $k => $item) {
    if ($active == $k) {
      $class = 'active';
      $done = FALSE;
    }
    else {
      $class = $done ? 'done' : '';
    }
    $output .= '<li class="' . $class . '">' . $item . '</li>';
  }
  $output .= '</ol>';
  return $output;
}

/**
 * Generate a themed installation page.
 *
 * Note: this function is not themeable.
 *
 * @param $content
 *   The page content to show.
 */
function theme_install_page($content) {
  drupal_set_header('Content-Type', 'text/html; charset=utf-8');

  // Assign content.
  $variables['content'] = $content;
  // Delay setting the message variable so it can be processed below.
  $variables['show_messages'] = FALSE;
  // Variable processors invoked manually since this function and theme_update_page()
  // are exceptions in how it works within the theme system.
  template_preprocess($variables, 'install_page');
  template_preprocess_maintenance_page($variables);
  template_process($variables, 'install_page');

  // Special handling of error messages
  $messages = drupal_set_message();
  if (isset($messages['error'])) {
    $title = count($messages['error']) > 1 ? st('The following errors must be resolved before you can continue the installation process') : st('The following error must be resolved before you can continue the installation process');
    $variables['messages'] .= '<h3>' . $title . ':</h3>';
    $variables['messages'] .= theme('status_messages', 'error');
    $variables['content'] .= '<p>' . st('Please check the error messages and <a href="!url">try again</a>.', array('!url' => request_uri())) . '</p>';
  }

  // Special handling of warning messages
  if (isset($messages['warning'])) {
    $title = count($messages['warning']) > 1 ? st('The following installation warnings should be carefully reviewed') : st('The following installation warning should be carefully reviewed');
    $variables['messages'] .= '<h4>' . $title . ':</h4>';
    $variables['messages'] .= theme('status_messages', 'warning');
  }

  // Special handling of status messages
  if (isset($messages['status'])) {
    $title = count($messages['status']) > 1 ? st('The following installation warnings should be carefully reviewed, but in most cases may be safely ignored') : st('The following installation warning should be carefully reviewed, but in most cases may be safely ignored');
    $variables['messages'] .= '<h4>' . $title . ':</h4>';
    $variables['messages'] .= theme('status_messages', 'status');
  }

  // This was called as a theme hook (not template), so we need to
  // fix path_to_theme() for the template, to point at the actual
  // theme rather than system module as owner of the hook.
  global $theme_path;
  $theme_path = 'themes/garland';

  return theme_render_template('themes/garland/maintenance-page.tpl.php', $variables);
}

/**
 * Generate a themed update page.
 *
 * Note: this function is not themeable.
 *
 * @param $content
 *   The page content to show.
 * @param $show_messages
 *   Whether to output status and error messages.
 *   FALSE can be useful to postpone the messages to a subsequent page.
 */
function theme_update_page($content, $show_messages = TRUE) {
  // Set required headers.
  drupal_set_header('Content-Type', 'text/html; charset=utf-8');

  // Assign content and show message flag.
  $variables['content'] = $content;
  $variables['show_messages'] = $show_messages;
  // Variable processors invoked manually since this function and theme_install_page()
  // are exceptions in how it works within the theme system.
  template_preprocess($variables, 'update_page');
  template_preprocess_maintenance_page($variables);
  template_process($variables, 'update_page');

  // Special handling of warning messages.
  $messages = drupal_set_message();
  if (isset($messages['warning'])) {
    $title = count($messages['warning']) > 1 ? 'The following update warnings should be carefully reviewed before continuing' : 'The following update warning should be carefully reviewed before continuing';
    $variables['messages'] .= '<h4>' . $title . ':</h4>';
    $variables['messages'] .= theme('status_messages', 'warning');
  }

  // This was called as a theme hook (not template), so we need to
  // fix path_to_theme() for the template, to point at the actual
  // theme rather than system module as owner of the hook.
  global $theme_path;
  $theme_path = 'themes/garland';

  return theme_render_template('themes/garland/maintenance-page.tpl.php', $variables);
}
