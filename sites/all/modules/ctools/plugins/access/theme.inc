<?php
// $Id: theme.inc,v 1.2 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Plugin to provide access control based on user themeission strings.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("Current theme"),
  'description' => t('Control access by checking which theme is in use.'),
  'callback' => 'ctools_theme_ctools_access_check',
  'default' => array('theme' => variable_get('theme_default', 'garland')),
  'settings form' => 'ctools_theme_ctools_access_settings',
  'summary' => 'ctools_theme_ctools_access_summary',
);

/**
 * Settings form for the 'by theme' access plugin
 */
function ctools_theme_ctools_access_settings($form, &$form_state, $conf) {
  $themes = array();
  foreach (list_themes() as $key => $theme) {
    $themes[$key] = $theme->info['name'];
  }

  $form['settings']['theme'] = array(
    '#type' => 'select',
    '#options' => $themes,
    '#title' => t('Themes'),
    '#default_value' => $conf['theme'],
    '#description' => t('This will only be accessed if the current theme is the selected theme.'),
  );
  return $form;
}

/**
 * Check for access.
 */
function ctools_theme_ctools_access_check($conf, $context) {
  if (!empty($GLOBALS['theme'])) {
    $theme = $GLOBALS['theme'];
  }
  else if (!empty($GLOBALS['custom_theme'])) {
    $theme = $GLOBALS['custom_theme'];
  }
  else if (!empty($GLOBALS['user']->theme)) {
    $theme = $GLOBALS['user']->theme;
  }
  else {
    $theme = variable_get('theme_default', 'garland');
  }

  return $conf['theme'] == $theme;
}

/**
 * Provide a summary description based upon the checked roles.
 */
function ctools_theme_ctools_access_summary($conf, $context) {
  if (!isset($conf['theme'])) {
    return t('Error, unset theme');
  }
  $themes = list_themes();

  return t('Current theme is "@theme"', array('@theme' => $themes[$conf['theme']]->info['name']));
}
