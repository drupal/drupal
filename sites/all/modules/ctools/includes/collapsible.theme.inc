<?php
// $Id: collapsible.theme.inc,v 1.6 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Theme function for the collapsible div tool.
 *
 * Call theme('ctools_collapsible', $handle, $content, $collapsed) to draw the
 * div. The theme function is not necessary; you can add the classes, js and css
 * yourself if you really want to.
 */

/**
 * Delegated implementation of hook_theme()
 */
function ctools_collapsible_theme(&$items) {
  $items['ctools_collapsible'] = array(
    'variables' => array('handle' => NULL, 'content' => NULL, 'collapsed' => FALSE),
    'file' => 'includes/collapsible.theme.inc',
  );
  $items['ctools_collapsible_remembered'] = array(
    'variables' => array('id' => NULL, 'handle' => NULL, 'content' => NULL, 'collapsed' => FALSE),
    'file' => 'includes/collapsible.theme.inc',
  );
}

/**
 * Render a collapsible div.
 *
 * @param $handle
 *   Text to put in the handle/title area of the div.
 * @param $content
 *   Text to put in the content area of the div, this is what will get
 *   collapsed
 * @param $collapsed = FALSE
 *   If true, this div will start out collapsed.
 */
function theme_ctools_collapsible($vars) {
  ctools_add_js('collapsible-div');
  ctools_add_css('collapsible-div');

  $class = $vars['collapsed'] ? ' ctools-collapsed' : '';
  $output = '<div class="ctools-collapsible-container' . $class . '">';
  $output .= '<div class="ctools-collapsible-handle">' . $vars['handle'] . '</div>';
  $output .= '<div class="ctools-collapsible-content">' . $vars['content'] . '</div>';
  $output .= '</div>';

  return $output;
}

/**
 * Render a collapsible div whose state will be remembered.
 *
 * @param $id
 *   The CSS id of the container. This is required.
 * @param $handle
 *   Text to put in the handle/title area of the div.
 * @param $content
 *   Text to put in the content area of the div, this is what will get
 *   collapsed
 * @param $collapsed = FALSE
 *   If true, this div will start out collapsed.
 */
function theme_ctools_collapsible_remembered($vars) {
  $id = $vars['id'];
  $handle = $vars['handle'];
  $content = $vars['content'];
  $collapsed = $vars['collapsed'];
  ctools_add_js('collapsible-div');
  ctools_add_css('collapsible-div');

  $class = $collapsed ? ' ctools-collapsed' : '';
  $output = '<div id="' . $id . '" class="ctools-collapsible-remember ctools-collapsible-container' . $class . '">';
  $output .= '<div class="ctools-collapsible-handle">' . $handle . '</div>';
  $output .= '<div class="ctools-collapsible-content">' . $content . '</div>';
  $output .= '</div>';

  return $output;
}

