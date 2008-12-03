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
 * Render HTML for a set of filter tips.
 *
 * @param $tips
 *   An array containing descriptions and a CSS id in the form of
 *   'module-name/filter-id' (only used when $long is TRUE) for each input
 *   filter in one or more input formats. Example:
 *   @code
 *     array(
 *       'Full HTML' => array(
 *         0 => array(
 *           'tip' => 'Web page addresses and e-mail addresses turn into links automatically.',
 *           'id' => 'filter/2',
 *         ),
 *       ),
 *     );
 *   @endcode
 * @param $long
 *   (optional) Whether the passed in filter tips contain extended explanations,
 *   i.e. intended to be output on the path 'filter/tips' (TRUE), or are in a
 *   short format, i.e. suitable to be displayed below a form element.  Defaults
 *   to FALSE.
 *
 * @see _filter_tips()
 * @ingroup themeable
 */
function theme_filter_tips($tips, $long = FALSE) {
  $output = '';

  $multiple = count($tips) > 1;
  if ($multiple) {
    $output = t('Input formats') . ':';
  }

  if (count($tips)) {
    if ($multiple) {
      $output .= '<ul>';
    }
    foreach ($tips as $name => $tiplist) {
      if ($multiple) {
        $output .= '<li>';
        $output .= '<strong>' . $name . '</strong>:<br />';
      }

      if (count($tiplist) > 0) {
        $output .= '<ul class="tips">';
        foreach ($tiplist as $tip) {
          $output .= '<li' . ($long ? ' id="filter-' . str_replace("/", "-", $tip['id']) . '">' : '>') . $tip['tip'] . '</li>';
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
