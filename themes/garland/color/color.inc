<?php

$info = array(

  // Available colors and color labels used in theme.
  'fields' => array(
    'base' => t('Base color'),
    'link' => t('Link color'),
    'top' => t('Header top'),
    'bottom' => t('Header bottom'),
    'text' => t('Text color'),
  ),
  // Pre-defined color schemes.
  'schemes' => array(
    'default' => array(
      'title' => t('Blue Lagoon (Default)'),
      'colors' => array(
        'base' => '#0072b9',
        'link' => '#027ac6',
        'top' => '#2385c2',
        'bottom' => '#5ab5ee',
        'text' => '#494949',
      ),
    ),
    'ash' => array(
      'title' => t('Ash'),
      'colors' => array(
        'base' => '#464849',
        'link' => '#2f416f',
        'top' => '#2a2b2d',
        'bottom' => '#5d6779',
      ),
    ),
    'aquamarine' => array(
      'title' => t('Aquamarine'),
      'colors' => array(
        'base' => '#55c0e2',
        'link' => '#000000',
        'text' => '#696969',
        'top' => '#085360',
        'bottom' => '#007e94',
      ),
    ),
    'chocolate' => array(
      'title' => t('Belgian Chocolate'),
      'colors' => array(
        'base' => '#d5b048',
        'link' => '#6c420e',
        'top' => '#331900',
        'bottom' => '#971702',
      ),
    ),
    'bluemarine' => array(
      'title' => t('Bluemarine'),
      'colors' => array(
        'base' => '#3f3f3f',
        'link' => '#336699',
        'text' => '#000000',
        'top' => '#6598cb',
        'bottom' => '#6598cb',
      ),
    ),
    'citrus' => array(
      'title' => t('Citrus Blast'),
      'colors' => array(
        'base' => '#d0cb9a',
        'link' => '#917803',
        'top' => '#efde01',
        'bottom' => '#e6fb2d',
      ),
    ),
    'cold' => array(
      'title' => t('Cold Day'),
      'colors' => array(
        'base' => '#0f005c',
        'link' => '#434f8c',
        'text' => '#000000',
        'top' => '#4d91ff',
        'bottom' => '#1a1575',
      ),
    ),
    'greenbeam' => array(
      'title' => t('Greenbeam'),
      'colors' => array(
        'base' => '#c9c497',
        'link' => '#0c7a00',
        'top' => '#03961e',
        'bottom' => '#7be000',
      ),
    ),
    'mediterrano' => array(
      'title' => t('Mediterrano'),
      'colors' => array(
        'base' => '#ffe23d',
        'link' => '#a9290a',
        'top' => '#fc6d1d',
        'bottom' => '#a30f42',
      ),
    ),
    'mercury' => array(
      'title' => t('Mercury'),
      'colors' => array(
        'base' => '#788597',
        'link' => '#3f728d',
        'top' => '#a9adbc',
        'bottom' => '#d4d4d4',
        'text' => '#707070',
      ),
    ),
    'nocturnal' => array(
      'title' => t('Nocturnal'),
      'colors' => array(
        'base' => '#5b5fa9',
        'link' => '#5b5faa',
        'top' => '#0a2352',
        'bottom' => '#9fa8d5',
      ),
    ),
    'olivia' => array(
      'title' => t('Olivia'),
      'colors' => array(
        'base' => '#7db323',
        'link' => '#6a9915',
        'top' => '#b5d52a',
        'bottom' => '#7db323',
        'text' => '#191a19',
      ),
    ),
    'pink_plastic' => array(
      'title' => t('Pink Plastic'),
      'colors' => array(
        'base' => '#12020b',
        'link' => '#1b1a13',
        'top' => '#f391c6',
        'bottom' => '#f41063',
        'text' => '#898080',
      ),
    ),
    'shiny_tomato' => array(
      'title' => t('Shiny Tomato'),
      'colors' => array(
        'base' => '#b7a0ba',
        'link' => '#c70000',
        'top' => '#a1443a',
        'bottom' => '#f21107',
        'text' => '#515d52',
      ),
    ),
    'teal_top' => array(
      'title' => t('Teal Top'),
      'colors' => array(
        'base' => '#18583d',
        'link' => '#1b5f42',
        'top' => '#34775a',
        'bottom' => '#52bf90',
        'text' => '#2d2d2d',
      ),
    ),
  ),

  // Images to copy over.
  'copy' => array(
    'images/menu-collapsed.gif',
    'images/menu-collapsed-rtl.gif',
    'images/menu-expanded.gif',
    'images/menu-leaf.gif',
  ),

  // CSS files (excluding @import) to rewrite with new color scheme.
  'css' => array(
    'style.css',
  ),

  // Gradient definitions.
  'gradients' => array(
    array(
      // (x, y, width, height).
      'dimension' => array(0, 38, 760, 121),
      // Direction of gradient ('vertical' or 'horizontal').
      'direction' => 'vertical',
      // Keys of colors to use for the gradient.
      'colors' => array('top', 'bottom'),
    ),
  ),

  // Color areas to fill (x, y, width, height).
  'fill' => array(
    'base' => array(0, 0, 760, 568),
    'link' => array(107, 533, 41, 23),
  ),

  // Coordinates of all the theme slices (x, y, width, height)
  // with their filename as used in the stylesheet.
  'slices' => array(
    'images/body.png'                      => array(0, 37, 1, 280),
    'images/bg-bar.png'                    => array(202, 530, 76, 14),
    'images/bg-bar-white.png'              => array(202, 506, 76, 14),
    'images/bg-tab.png'                    => array(107, 533, 41, 23),
    'images/bg-navigation.png'             => array(0, 0, 7, 37),
    'images/bg-content-left.png'           => array(40, 117, 50, 352),
    'images/bg-content-right.png'          => array(510, 117, 50, 352),
    'images/bg-content.png'                => array(299, 117, 7, 200),
    'images/bg-navigation-item.png'        => array(32, 37, 17, 12),
    'images/bg-navigation-item-hover.png'  => array(54, 37, 17, 12),
    'images/gradient-inner.png'            => array(646, 307, 112, 42),

    'logo.png'                             => array(622, 51, 64, 73),
    'screenshot.png'                       => array(0, 37, 400, 240),
  ),

  // Reference color used for blending. Matches the base.png's colors.
  'blend_target' => '#ffffff',

  // Preview files.
  'preview_image' => 'color/preview.png',
  'preview_css' => 'color/preview.css',

  // Base file for image generation.
  'base_image' => 'color/base.png',
);
