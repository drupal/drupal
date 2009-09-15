<?php
// $Id$

/**
 * Override or insert variables into the page template.
 */
function seven_process_html(&$vars) {
  $vars['ie_styles'] = '<!--[if lt IE 7]><style type="text/css" media="screen">@import ' . path_to_theme() . '/ie6.css";</style><![endif]-->';
}
function seven_preprocess_page(&$vars) {
  $vars['primary_local_tasks'] = menu_primary_local_tasks();
  $vars['secondary_local_tasks'] = menu_secondary_local_tasks();
}

/**
 * Display the list of available node types for node creation.
 */
function seven_node_add_list($content) {
  $output = '';
  if ($content) {
    $output = '<ul class="node-type-list">';
    foreach ($content as $item) {
      $output .= '<li class="clearfix">';
      $output .= '<span class="label">' . l($item['title'], $item['href'], $item['localized_options']) . '</span>';
      $output .= '<div class="description">' . filter_xss_admin($item['description']) . '</div>';
      $output .= '</li>';
    }
    $output .= '</ul>';
  }
  return $output;
}

/**
 * Override of theme_admin_block_content().
 *
 * Use unordered list markup in both compact and extended move.
 */
function seven_admin_block_content($content) {
  $output = '';
  if (!empty($content)) {
    $output = system_admin_compact_mode() ? '<ul class="admin-list compact">' : '<ul class="admin-list">';
    foreach ($content as $item) {
      $output .= '<li class="leaf">';
      $output .= l($item['title'], $item['href'], $item['localized_options']);
      if (!system_admin_compact_mode()) {
        $output .= '<div class="description">' . $item['description'] . '</div>';
      }
      $output .= '</li>';
    }
    $output .= '</ul>';
  }
  return $output;
}

/**
 * Override of theme_tablesort_indicator().
 *
 * Use our own image versions, so they show up as black and not gray on gray.
 */
function seven_tablesort_indicator($style) {
  $theme_path = drupal_get_path('theme', 'seven');
  if ($style == "asc") {
    return theme('image', $theme_path . '/images/arrow-asc.png', t('sort icon'), t('sort ascending'));
  }
  else {
    return theme('image', $theme_path . '/images/arrow-desc.png', t('sort icon'), t('sort descending'));
  }
}

/**
 * Override of theme_fieldset().
 *
 * Add span to legend tag, so we can style it to be inside the fieldset.
 */
function seven_fieldset($element) {
  if (!empty($element['#collapsible'])) {
    drupal_add_js('misc/collapse.js');

    if (!isset($element['#attributes']['class'])) {
      $element['#attributes']['class'] = array();
    }

    $element['#attributes']['class'][] = 'collapsible';
    if (!empty($element['#collapsed'])) {
      $element['#attributes']['class'][] = 'collapsed';
    }
  }
  $element['#attributes']['id'] = $element['#id'];

  return '<fieldset' . drupal_attributes($element['#attributes']) . '>' . ($element['#title'] ? '<legend><span>' . $element['#title'] . '</span></legend>' : '') . (isset($element['#description']) && $element['#description'] ? '<div class="fieldset-description">' . $element['#description'] . '</div>' : '') . (!empty($element['#children']) ? $element['#children'] : '') . (isset($element['#value']) ? $element['#value'] : '') . "</fieldset>\n";
}
