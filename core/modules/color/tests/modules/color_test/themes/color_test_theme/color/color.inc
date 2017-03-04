<?php

/**
 * @file
 * Lists available colors and color schemes for the Color test theme.
 */

$info = [
  'fields' => [
    'bg' => t('Main background'),
    'text' => t('Text color'),
  ],
  'schemes' => [
    'default' => [
      'title' => t('Default'),
      'colors' => [
        'bg' => '#ff0000',
        'text' => '#0000ff',
      ],
    ],
    'custom' => [
      'title' => t('Custom'),
      'colors' => [
        'bg' => '#ff0000',
        'text' => '#3b3b3b',
      ],
    ],
  ],
  'css' => [
    'css/colors.css',
  ],
  'preview_html' => 'color/preview.html',
];
