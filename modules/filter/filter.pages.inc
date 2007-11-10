<?php
// $Id$

/**
 * @file
 * User page callbacks for the filter module.
 */


/**
 * Menu callback; show a page with long filter tips.
 */
function filter_tips_long() {
  $format = arg(2);
  if ($format) {
    $output = theme('filter_tips', _filter_tips($format, TRUE), TRUE);
  }
  else {
    $output = theme('filter_tips', _filter_tips(-1, TRUE), TRUE);
  }
  return $output;
}


/**
 * Format a set of filter tips.
 *
 * @ingroup themeable
 */
function theme_filter_tips($tips, $long = FALSE, $extra = '') {
  $output = '';

  $multiple = count($tips) > 1;
  if ($multiple) {
    $output = t('input formats') .':';
  }

  if (count($tips)) {
    if ($multiple) {
      $output .= '<ul>';
    }
    foreach ($tips as $name => $tiplist) {
      if ($multiple) {
        $output .= '<li>';
        $output .= '<strong>'. $name .'</strong>:<br />';
      }

      if (count($tiplist) > 0) {
        $output .= '<ul class="tips">';
        foreach ($tiplist as $tip) {
          $output .= '<li'. ($long ? ' id="filter-'. str_replace("/", "-", $tip['id']) .'">' : '>') . $tip['tip'] .'</li>';
        }
        $output .= '</ul>';
      }

      if ($multiple) {
        $output .= '</li>';
      }
    }
    if ($multiple) {
      $output .= '</ul>';
    }
  }

  return $output;
}
