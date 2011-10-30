<?php

/**
 * @file
 * User page callbacks for the filter module.
 */


/**
 * Menu callback; show a page with long filter tips.
 */
function filter_tips_long() {
  $format_id = arg(2);
  if ($format_id) {
    $output = theme('filter_tips', array('tips' => _filter_tips($format_id, TRUE), 'long' => TRUE));
  }
  else {
    $output = theme('filter_tips', array('tips' => _filter_tips(-1, TRUE), 'long' => TRUE));
  }
  return $output;
}


/**
 * Returns HTML for a set of filter tips.
 *
 * @param $variables
 *   An associative array containing:
 *   - tips: An array containing descriptions and a CSS id in the form of
 *     'module-name/filter-id' (only used when $long is TRUE) for each
 *     filter in one or more text formats. Example:
 *     @code
 *       array(
 *         'Full HTML' => array(
 *           0 => array(
 *             'tip' => 'Web page addresses and e-mail addresses turn into links automatically.',
 *             'id' => 'filter/2',
 *           ),
 *         ),
 *       );
 *     @endcode
 *   - long: (optional) Whether the passed-in filter tips contain extended
 *     explanations, i.e. intended to be output on the path 'filter/tips'
 *     (TRUE), or are in a short format, i.e. suitable to be displayed below a
 *     form element. Defaults to FALSE.
 *
 * @see _filter_tips()
 * @ingroup themeable
 */
function theme_filter_tips($variables) {
  $tips = $variables['tips'];
  $long = $variables['long'];
  $output = '';

  $multiple = count($tips) > 1;
  if ($multiple) {
    $output = '<h2>' . t('Text Formats') . '</h2>';
  }

  if (count($tips)) {
    if ($multiple) {
      $output .= '<div class="compose-tips">';
    }
    foreach ($tips as $name => $tiplist) {
      if ($multiple) {
        $output .= '<div class="filter-type filter-' . drupal_html_class($name) . '">';
        $output .= '<h3>' . $name . '</h3>';
      }

      if (count($tiplist) > 0) {
        $output .= '<ul class="tips">';
        foreach ($tiplist as $tip) {
          $output .= '<li' . ($long ? ' id="filter-' . str_replace("/", "-", $tip['id']) . '">' : '>') . $tip['tip'] . '</li>';
        }
        $output .= '</ul>';
      }

      if ($multiple) {
        $output .= '</div>';
      }
    }
    if ($multiple) {
      $output .= '</div>';
    }
  }

  return $output;
}
