<?php

/**
 * @file
 * Miscellaneous functions.
 */

use Drupal\Component\Utility\Variable;

/**
 * Drupal-friendly var_export().
 *
 * @param mixed $var
 *   The variable to export.
 * @param string $prefix
 *   A prefix that will be added at the beginning of every lines of the output.
 *
 * @return string
 *   The variable exported in a way compatible to Drupal's coding standards.
 *
 * @deprecated Use \Drupal\Component\Utility\Variable::export().
 */
function drupal_var_export($var, $prefix = '') {
  return Variable::export($var, $prefix);
}
