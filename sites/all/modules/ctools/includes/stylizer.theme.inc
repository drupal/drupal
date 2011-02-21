<?php
// $Id: stylizer.theme.inc,v 1.3 2011/01/05 19:41:11 merlinofchaos Exp $

/**
 * @file
 * Contains theme registry and theme implementations for the content types.
 */

/**
 * Implementation of hook_theme to load all content plugins and pass thru if
 * necessary.
 */
function ctools_stylizer_theme(&$theme) {
  $theme['ctools_stylizer_color_scheme_form'] = array(
    'render element' => 'form',
    'file' => 'includes/stylizer.inc',
  );

  $theme['ctools_stylizer_preview_form'] = array(
    'render element' => 'form',
    'file' => 'includes/stylizer.inc',
  );

  $theme['ctools_style_icon'] = array(
    'variables' => array('image' => NULL, 'title' => NULL),
    'file' => 'includes/stylizer.inc',
  );
}

