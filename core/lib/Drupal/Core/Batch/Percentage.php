<?php

/**
 * @file
 * Contains \Drupal\Core\Batch\Percentage.
 */

namespace Drupal\Core\Batch;

/**
 * Helper methods for the batch system.
 */
class Percentage {

  /**
   * Formats the percent completion for a batch set.
   *
   * @param int $total
   *   The total number of operations.
   * @param int $current
   *   The number of the current operation. This may be a floating point number
   *   rather than an integer in the case of a multi-step operation that is not
   *   yet complete; in that case, the fractional part of $current represents the
   *   fraction of the operation that has been completed.
   *
   * @return string
   *   The properly formatted percentage, as a string. We output percentages
   *   using the correct number of decimal places so that we never print "100%"
   *   until we are finished, but we also never print more decimal places than
   *   are meaningful.
   *
   * @see _batch_process()
   */
  public static function format($total, $current) {
    if (!$total || $total == $current) {
      // If $total doesn't evaluate as true or is equal to the current set, then
      // we're finished, and we can return "100".
      $percentage = '100';
    }
    else {
      // We add a new digit at 200, 2000, etc. (since, for example, 199/200
      // would round up to 100% if we didn't).
      $decimal_places = max(0, floor(log10($total / 2.0)) - 1);
      do {
        // Calculate the percentage to the specified number of decimal places.
        $percentage = sprintf('%01.' . $decimal_places . 'f', round($current / $total * 100, $decimal_places));
        // When $current is an integer, the above calculation will always be
        // correct. However, if $current is a floating point number (in the case
        // of a multi-step batch operation that is not yet complete), $percentage
        // may be erroneously rounded up to 100%. To prevent that, we add one
        // more decimal place and try again.
        $decimal_places++;
      } while ($percentage == '100');
    }
    return $percentage;
  }

}
