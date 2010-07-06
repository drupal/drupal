<?php
// $Id: template.php,v 1.1 2010/07/06 05:25:51 webchick Exp $

/**
 * Add body classes if certain regions have content.
 */
function bartik_preprocess_html(&$variables) {
  if (!empty($variables['page']['featured'])) {
    $variables['classes_array'][] = 'featured';
  }

  if (!empty($variables['page']['triptych_first'])
    || !empty($variables['page']['triptych_middle'])
    || !empty($variables['page']['triptych_last'])) {
    $variables['classes_array'][] = 'triptych';
  }

  if (!empty($variables['page']['footer_firstcolumn'])
    || !empty($variables['page']['footer_secondcolumn'])
    || !empty($variables['page']['footer_thirdcolumn'])
    || !empty($variables['page']['footer_fourthcolumn'])) {
    $variables['classes_array'][] = 'footer-columns';
  }

  // Add conditional stylesheets for IE
  drupal_add_css(path_to_theme() . '/css/ie.css', array('weight' => CSS_THEME, 'browsers' => array('IE' => 'lte IE 7', '!IE' => FALSE), 'preprocess' => FALSE));
  drupal_add_css(path_to_theme() . '/css/ie6.css', array('weight' => CSS_THEME, 'browsers' => array('IE' => 'IE 6', '!IE' => FALSE), 'preprocess' => FALSE));
}

/**
 * Override or insert variables into the page template for HTML output.
 */
function bartik_process_html(&$variables) {
  // Hook into color.module.
  if (module_exists('color')) {
    _color_html_alter($variables);
  }
}

/**
 * Override or insert variables into the page template.
 */
function bartik_process_page(&$variables) {
  // Hook into color.module.
  if (module_exists('color')) {
    _color_page_alter($variables);
  }
  // Always print the site name and slogan, but if they are toggled off, we'll
  // just hide them visually.
  $variables['hide_site_name']   = theme_get_setting('toggle_name') ? FALSE : TRUE;
  $variables['hide_site_slogan'] = theme_get_setting('toggle_slogan') ? FALSE : TRUE;
  if ($variables['hide_site_name']) {
    // If toggle_name is FALSE, the site_name will be empty, so we rebuild it.
    $variables['site_name'] = filter_xss_admin(variable_get('site_name', 'Drupal'));
  }
  if ($variables['hide_site_slogan']) {
    // If toggle_site_slogan is FALSE, the site_slogan will be empty, so we rebuild it.
    $variables['site_slogan'] = filter_xss_admin(variable_get('site_slogan', ''));
  }
  // Since the title and the shortcut link are both block level elements,
  // positioning them next to each other is much simpler with a wrapper div.
  if (!empty($variables['title_suffix']['add_or_remove_shortcut']) && $variables['title']) {
    // Add a wrapper div using the title_prefix and title_suffix render elements.
    $variables['title_prefix']['shortcut_wrapper'] = array(
      '#markup' => '<div class="shortcut-wrapper clearfix">',
      '#weight' => 100,
    );
    $variables['title_suffix']['shortcut_wrapper'] = array(
      '#markup' => '</div>',
      '#weight' => -99,
    );
    // Make sure the shortcut link is the first item in title_suffix.
    $variables['title_suffix']['add_or_remove_shortcut']['#weight'] = -100;
  }
}

/**
 * Override or insert variables into the maintenance page template.
 */
function bartik_process_maintenance_page(&$variables) {
  // Always print the site name and slogan, but if they are toggled off, we'll
  // just hide them visually.
  $variables['hide_site_name']   = theme_get_setting('toggle_name') ? FALSE : TRUE;
  $variables['hide_site_slogan'] = theme_get_setting('toggle_slogan') ? FALSE : TRUE;
  if ($variables['hide_site_name']) {
    // If toggle_name is FALSE, the site_name will be empty, so we rebuild it.
    $variables['site_name'] = filter_xss_admin(variable_get('site_name', 'Drupal'));
  }
  if ($variables['hide_site_slogan']) {
    // If toggle_site_slogan is FALSE, the site_slogan will be empty, so we rebuild it.
    $variables['site_slogan'] = filter_xss_admin(variable_get('site_slogan', ''));
  }
}

/**
 * Override or insert variables into the block template.
 */
function bartik_preprocess_block(&$variables) {
  // In the header region, visually hide the title of any menu block or of the
  // user login block, but leave it accessible.
  if ($variables['block']->region == 'header' && ($variables['block']->module == 'menu' || $variables['block']->module == 'user' && $variables['block']->delta == 'login')) {
    $variables['title_attributes_array']['class'][] = 'element-invisible';
  }
  // System menu blocks should get the same class as menu module blocks.
  if (in_array($variables['block']->delta, array_keys(menu_list_system_menus()))) {
    $variables['classes_array'][] = 'block-menu';
    // Also, hide the title if its in the header region.
    if ($variables['block']->region == 'header') {
      $variables['title_attributes_array']['class'][] = 'element-invisible';
    }
  }
  // Set "first" and "last" classes.
  if ($variables['block']->position_first){
    $variables['classes_array'][] = 'first';
  }
  if ($variables['block']->position_last){
    $variables['classes_array'][] = 'last';
  }
  // Set "odd" & "even" classes.
  $variables['classes_array'][] = $variables['block']->position % 2 == 0 ? 'odd' : 'even';
}

/**
 * Implements hook_page_alter().
 */
function bartik_page_alter(&$page) {
  // Determine the position and count of blocks within regions.
  foreach ($page as &$region) {
    // Make sure this is a "region" element.
    if (is_array($region) && isset($region['#region'])) {
      $i = 0;
      foreach ($region as &$block) {
        // Make sure this is a "block" element.
        if (is_array($block) && isset($block['#block'])) {
          $block['#block']->position = $i++;
          // Set a flag for "first" and "last" blocks.
          $block['#block']->position_first = ($block['#block']->position == 0);
          $block['#block']->position_last = FALSE;
          $last_block =& $block;
        }
      }
      $last_block['#block']->position_last = TRUE;
      $region['#block_count'] = $i;
    }
  }
}
