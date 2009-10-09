<?php
// $Id$

/**
 * Return a themed breadcrumb trail.
 *
 * @param $breadcrumb
 *   An array containing the breadcrumb links.
 * @return a string containing the breadcrumb output.
 */
function garland_breadcrumb($variables) {
  $breadcrumb = $variables['breadcrumb'];

  if (!empty($breadcrumb)) {
    // Provide a navigational heading to give context for breadcrumb links to
    // screen-reader users. Make the heading invisible with .element-invisible.
    $output = '<h2 class="element-invisible">' . t('You are here') . '</h2>';

    $output .= '<div class="breadcrumb">' . implode(' › ', $breadcrumb) . '</div>';
    return $output;
  }
}

/**
 * Override or insert variables into the html template.
 */
function garland_process_html(&$vars) {
  // Hook into color.module
  if (module_exists('color')) {
    _color_html_alter($vars);
  }
  $vars['styles'] .= "\n<!--[if lt IE 7]>\n" . garland_get_ie_styles() . "<![endif]-->\n";
}

/**
 * Override or insert variables into the page template.
 */
function garland_preprocess_page(&$vars) {
  $vars['tabs2'] = menu_secondary_local_tasks();
  if (isset($vars['main_menu'])) {
    $vars['primary_nav'] = theme('links', array(
      'links' => $vars['main_menu'],
      'attributes' => array(
        'class' => array('links', 'main-menu'),
      ),
      'heading' => array(
        'text' => t('Main menu'),
        'level' => 'h2',
        'class' => array('element-invisible'),
      )
    ));
  }
  else {
    $vars['primary_nav'] = FALSE;
  }
  if (isset($vars['secondary_menu'])) {
    $vars['secondary_nav'] = theme('links', array(
      'links' => $vars['secondary_menu'],
      'attributes' => array(
        'class' => array('links', 'secondary-menu'),
      ),
      'heading' => array(
        'text' => t('Secondary menu'),
        'level' => 'h2',
        'class' => array('element-invisible'),
      )
    ));
  }
  else {
    $vars['secondary_nav'] = FALSE;
  }

  // Prepare header
  $site_fields = array();
  if (!empty($vars['site_name'])) {
    $site_fields[] = check_plain($vars['site_name']);
  }
  if (!empty($vars['site_slogan'])) {
    $site_fields[] = check_plain($vars['site_slogan']);
  }
  $vars['site_title'] = implode(' ', $site_fields);
  if (!empty($site_fields)) {
    $site_fields[0] = '<span>' . $site_fields[0] . '</span>';
  }
  $vars['site_html'] = implode(' ', $site_fields);

}

/**
 * Override or insert variables into the page template.
 */
function garland_process_page(&$vars) {
  // Hook into color.module
  if (module_exists('color')) {
    _color_page_alter($vars);
  }
}

/**
 * Override or insert variables into the region template.
 */
function garland_preprocess_region(&$vars) {
  if ($vars['region'] == 'header') {
    $vars['classes_array'][] = 'clearfix';
  }
}

/**
 * Returns the rendered local tasks. The default implementation renders
 * them as tabs. Overridden to split the secondary tasks.
 */
function garland_menu_local_tasks() {
  return menu_primary_local_tasks();
}

/**
 * Generates IE CSS links for LTR and RTL languages.
 */
function garland_get_ie_styles() {
  global $language;

  $ie_styles = '<link type="text/css" rel="stylesheet" media="all" href="' . file_create_url(path_to_theme() . '/fix-ie.css') . '" />' . "\n";
  if ($language->direction == LANGUAGE_RTL) {
    $ie_styles .= '      <style type="text/css" media="all">@import "' . file_create_url(path_to_theme() . '/fix-ie-rtl.css') . '";</style>' . "\n";
  }

  return $ie_styles;
}
