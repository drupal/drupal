<?php
// $Id: dropdown.theme.inc,v 1.7 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Provide a javascript based dropdown menu.
 *
 * The dropdown menu will show up as a clickable link; when clicked,
 * a small menu will slide down beneath it, showing the list of links.
 *
 * The dropdown will stay open until either the user has moved the mouse
 * away from the box for > .5 seconds, or can be immediately closed by
 * clicking the link again. The code is smart enough that if the mouse
 * moves away and then back within the .5 second window, it will not
 * re-close.
 *
 * Multiple dropdowns can be placed per page.
 *
 * If the user does not have javascript enabled, the link will not appear,
 * and instead by default the list of links will appear as a normal inline
 * list.
 *
 * The menu is heavily styled by default, and to make it look different
 * will require a little bit of CSS. You can apply your own class to the
 * dropdown to help ensure that your CSS can override the dropdown's CSS.
 *
 * In particular, the text, link, background and border colors may need to
 * be changed. Please see dropdown.css for information about the existing
 * styling.
 */

/**
 * Delegated implementation of hook_theme()
 */
function ctools_dropdown_theme(&$items) {
  $items['ctools_dropdown'] = array(
    'variables' => array('title' => NULL, 'links' => NULL, 'image' => FALSE, 'class' => ''),
    'file' => 'includes/dropdown.theme.inc',
  );
}

/**
 * Create a dropdown menu.
 *
 * @param $title
 *   The text to place in the clickable area to activate the dropdown.
 * @param $links
 *   A list of links to provide within the dropdown, suitable for use
 *   in via Drupal's theme('links').
 * @param $image
 *   If true, the dropdown link is an image and will not get extra decorations
 *   that a text dropdown link will.
 * @param $class
 *   An optional class to add to the dropdown's container div to allow you
 *   to style a single dropdown however you like without interfering with
 *   other dropdowns.
 */
function theme_ctools_dropdown($vars) { 
  // Provide a unique identifier for every dropdown on the page.
  static $id = 0;
  $id++;

  $class = 'ctools-dropdown-no-js ctools-dropdown' . ($vars['class'] ? (' ' . $vars['class']) : '');

  ctools_add_js('dropdown');
  ctools_add_css('dropdown');

  $output = '';

  $output .= '<div class="' . $class . '" id="ctools-dropdown-' . $id . '">';
  $output .= '<div class="ctools-dropdown-link-wrapper">';
  if ($vars['image']) {
    $output .= '<a href="#" class="ctools-dropdown-link ctools-dropdown-image-link">' . $vars['title'] . '</a>';
  }
  else {
    $output .= '<a href="#" class="ctools-dropdown-link ctools-dropdown-text-link">' . check_plain($vars['title']) . '</a>';
  }

  $output .= '</div>'; // wrapper
  $output .= '<div class="ctools-dropdown-container-wrapper">';
  $output .= '<div class="ctools-dropdown-container">';
  $output .= theme_links(array('links' => $vars['links'], 'attributes' => array(), 'heading' => ''));
  $output .= '</div>'; // container
  $output .= '</div>'; // container wrapper
  $output .= '</div>'; // dropdown
  return $output;
}

