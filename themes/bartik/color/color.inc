<?php

// Put the logo path into JavaScript for the live preview.
drupal_add_js(array('color' => array('logo' => theme_get_setting('logo', 'bartik'))), 'setting');

$info = array(
  // Available colors and color labels used in theme.
  'fields' => array(
    'bg' => t('Main background'),
    'link' => t('Link color'),
    'top' => t('Header top'),
    'bottom' => t('Header bottom'),
    'text' => t('Text color'),
    'sidebar' => t('Sidebar background'),
    'sidebarborders' => t('Sidebar borders'),
    'footer' => t('Footer background'),
    'titleslogan' => t('Title and slogan'),
  ),
  // Pre-defined color schemes.
  'schemes' => array(
    'default' => array(
      'title' => t('Blue Lagoon (default)'),
      'colors' => array(
        'bg' => '#ffffff',
        'link' => '#0071B3',
        'top' => '#0779bf',
        'bottom' => '#48a9e4',
        'text' => '#3b3b3b',
        'sidebar' => '#f6f6f2',
        'sidebarborders' => '#f9f9f9',
        'footer' => '#292929',
        'titleslogan' => '#fffeff',
      ),
    ),
    'firehouse' => array(
      'title' => t('Firehouse'),
      'colors' => array(
        'bg' => '#ffffff',
        'link' => '#d6121f',
        'top' => '#cd2d2d',
        'bottom' => '#cf3535',
        'text' => '#3b3b3b',
        'sidebar' => '#f1f4f0',
        'sidebarborders' => '#ededed',
        'footer' => '#1f1d1c',
        'titleslogan' => '#fffeff',
      ),
    ),
    'ice' => array(
      'title' => t('Ice'),
      'colors' => array(
        'bg' => '#ffffff',
        'link' => '#019dbf',
        'top' => '#d0d0d0',
        'bottom' => '#c2c4c5',
        'text' => '#4a4a4a',
        'sidebar' => '#ffffff',
        'sidebarborders' => '#cccccc',
        'footer' => '#24272c',
        'titleslogan' => '#000000',
      ),
    ),
    'plum' => array(
      'title' => t('Plum'),
      'colors' => array(
        'bg' => '#fffdf7',
        'link' => '#9d408d',
        'top' => '#4c1c58',
        'bottom' => '#593662',
        'text' => '#301313',
        'sidebar' => '#edede7',
        'sidebarborders' => '#e7e7e7',
        'footer' => '#2c2c28',
        'titleslogan' => '#ffffff',
      ),
    ),
    'slate' => array(
      'title' => t('Slate'),
      'colors' => array(
        'bg' => '#ffffff',
        'link' => '#0073b6',
        'top' => '#4a4a4a',
        'bottom' => '#4e4e4e',
        'text' => '#3b3b3b',
        'sidebar' => '#ffffff',
        'sidebarborders' => '#d0d0d0',
        'footer' => '#161617',
        'titleslogan' => '#ffffff',
      ),
    ),
  ),

  // CSS files (excluding @import) to rewrite with new color scheme.
  'css' => array(
    'css/colors.css',
  ),

  // Files to copy.
  'copy' => array(
    'logo.png',
  ),

  // Gradient definitions.
  'gradients' => array(
    array(
      // (x, y, width, height).
      'dimension' => array(0, 0, 0, 0),
      // Direction of gradient ('vertical' or 'horizontal').
      'direction' => 'vertical',
      // Keys of colors to use for the gradient.
      'colors' => array('top', 'bottom'),
    ),
  ),

  // Color areas to fill (x, y, width, height).
  'fill' => array(),

  // Coordinates of all the theme slices (x, y, width, height)
  // with their filename as used in the stylesheet.
  'slices' => array(),

  // Reference color used for blending. Matches the base.png's colors.
  'blend_target' => '#ffffff',

  // Preview files.
  'preview_css' => 'color/preview.css',
  'preview_js' => 'color/preview.js',
  'preview_html' => 'color/preview.html',

  // Base file for image generation.
  'base_image' => 'color/base.png',
);
