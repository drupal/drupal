<?php

/**
 * @file
 * Lists available colors and color schemes for the Color test theme.
 */

$info = array(
  'fields' => array(
    'bg' => t('Main background'),
    'text' => t('Text color'),
  ),
  'schemes' => array(
    'default' => array(
      'title' => t('Default'),
      'colors' => array(
        'bg' => '#ff0000',
        'text' => '#0000ff',
      ),
    ),
    'custom' => array(
      'title' => t('Custom'),
      'colors' => array(
        'bg' => '#ff0000',
        'text' => '#3b3b3b',
      ),
    ),
  ),
  'css' => array(
    'css/colors.css',
  ),
  'preview_html' => 'color/preview.html',
);
