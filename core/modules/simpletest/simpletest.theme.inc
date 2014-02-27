<?php

/**
 * @file
 * Page callbacks for simpletest module.
 */

/**
 * Returns HTML for the summary status of a simpletest result.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_simpletest_result_summary($variables) {
  $form = $variables['form'];
  return '<div class="simpletest-' . ($form['#ok'] ? 'pass' : 'fail') . '">' . _simpletest_format_summary_line($form) . '</div>';
}
