<?php
// $Id: color.inc,v 1.4 2007/12/14 17:00:14 goba Exp $

$info = array(

  // Pre-defined color schemes.
  'schemes' => array(
    '#0072b9,#027ac6,#2385c2,#5ab5ee,#494949' => t('Blue Lagoon (Default)'),
    '#464849,#2f416f,#2a2b2d,#5d6779,#494949' => t('Ash'),
    '#55c0e2,#000000,#085360,#007e94,#696969' => t('Aquamarine'),
    '#d5b048,#6c420e,#331900,#971702,#494949' => t('Belgian Chocolate'),
    '#3f3f3f,#336699,#6598cb,#6598cb,#000000' => t('Bluemarine'),
    '#d0cb9a,#917803,#efde01,#e6fb2d,#494949' => t('Citrus Blast'),
    '#0f005c,#434f8c,#4d91ff,#1a1575,#000000' => t('Cold Day'),
    '#c9c497,#0c7a00,#03961e,#7be000,#494949' => t('Greenbeam'),
    '#ffe23d,#a9290a,#fc6d1d,#a30f42,#494949' => t('Mediterrano'),
    '#788597,#3f728d,#a9adbc,#d4d4d4,#707070' => t('Mercury'),
    '#5b5fa9,#5b5faa,#0a2352,#9fa8d5,#494949' => t('Nocturnal'),
    '#7db323,#6a9915,#b5d52a,#7db323,#191a19' => t('Olivia'),
    '#12020b,#1b1a13,#f391c6,#f41063,#898080' => t('Pink Plastic'),
    '#b7a0ba,#c70000,#a1443a,#f21107,#515d52' => t('Shiny Tomato'),
    '#18583d,#1b5f42,#34775a,#52bf90,#2d2d2d' => t('Teal Top'),
  ),

  // Images to copy over.
  'copy' => array(
    '../images/menu-collapsed.gif',
    '../images/menu-collapsed-rtl.gif',
    '../images/menu-expanded.gif',
    '../images/menu-leaf.gif',
  ),

  // CSS files (excluding @import) to rewrite with new color scheme.
  'css' => array(
    '../style.css',
  ),

  // Coordinates of gradient (x, y, width, height).
  'gradient' => array(0, 37, 760, 121),

  // Color areas to fill (x, y, width, height).
  'fill' => array(
    'base' => array(0, 0, 760, 568),
    'link' => array(107, 533, 41, 23),
  ),

  // Coordinates of all the theme slices (x, y, width, height)
  // with their filename as used in the stylesheet.
  'slices' => array(
    '../images/body.png'                      => array(0, 37, 1, 280),
    '../images/bg-bar.png'                    => array(202, 530, 76, 14),
    '../images/bg-bar-white.png'              => array(202, 506, 76, 14),
    '../images/bg-tab.png'                    => array(107, 533, 41, 23),
    '../images/bg-navigation.png'             => array(0, 0, 7, 37),
    '../images/bg-content-left.png'           => array(40, 117, 50, 352),
    '../images/bg-content-right.png'          => array(510, 117, 50, 352),
    '../images/bg-content.png'                => array(299, 117, 7, 200),
    '../images/bg-navigation-item.png'        => array(32, 37, 17, 12),
    '../images/bg-navigation-item-hover.png'  => array(54, 37, 17, 12),
    '../images/gradient-inner.png'            => array(646, 307, 112, 42),

    'logo.png'                                => array(622, 51, 64, 73),
    'screenshot.png'                          => array(0, 37, 400, 240),
  ),

  // Reference color used for blending. Matches the base.png's colors.
  'blend_target' => '#ffffff',

  // Preview files.
  'preview_image' => 'color/preview.png',
  'preview_css' => '../color/preview.css',

  // Base file for image generation.
  'base_image' => 'color/base.png',
);
