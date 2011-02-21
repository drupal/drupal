<?php
// $Id: wysiwyg.dialog.inc,v 1.2 2009/08/08 07:11:25 sun Exp $

/**
 * @file
 * Wysiwyg dialog page handling functions.
 */

/**
 * Menu callback; Output a wysiwyg plugin dialog page.
 */
function wysiwyg_dialog($plugin, $instance) {
  $plugins = wysiwyg_get_all_plugins();
  if (!isset($plugins[$plugin])) {
    return drupal_access_denied();
  }
  $callback = $plugin . '_wysiwyg_dialog';
  if (!function_exists($callback)) {
    return drupal_not_found();
  }

  // Suppress admin menu.
  module_invoke('admin_menu', 'suppress');
  // Add editor instance id to Drupal.settings.
  $settings = array(
    'plugin' => $plugin,
    'instance' => $instance,
  );
  drupal_add_js(array('wysiwyg' => $settings), 'setting');

  echo theme('wysiwyg_dialog_page', $callback($instance));
}

/**
 * Template preprocess function for theme_wysiwyg_dialog_page().
 *
 * @see wysiwyg_dialog()
 * @see wysiwyg-dialog-page.tpl.php
 * @see template_preprocess()
 */
function template_preprocess_wysiwyg_dialog_page(&$variables) {
  // Construct page title
  $head_title = array(strip_tags(drupal_get_title()), variable_get('site_name', 'Drupal'));

  $variables['head_title']        = implode(' | ', $head_title);
  $variables['base_path']         = base_path();
  $variables['front_page']        = url();
  // @todo Would a breadcrumb make sense / possible at all?
  // $variables['breadcrumb']        = theme('breadcrumb', drupal_get_breadcrumb());
  $variables['head']              = drupal_get_html_head();
  $variables['help']              = theme('help');
  $variables['language']          = $GLOBALS['language'];
  $variables['language']->dir     = $GLOBALS['language']->direction ? 'rtl' : 'ltr';
  $variables['messages']          = $variables['show_messages'] ? theme('status_messages') : '';
  $variables['site_name']         = (theme_get_setting('toggle_name') ? variable_get('site_name', 'Drupal') : '');
  $variables['css']               = drupal_add_css();
  $variables['styles']            = drupal_get_css();
  $variables['scripts']           = drupal_get_js();
  $variables['tabs']              = theme('menu_local_tasks');
  $variables['title']             = drupal_get_title();
  // Closure should be filled last.
  $variables['closure']           = theme('closure');
}

