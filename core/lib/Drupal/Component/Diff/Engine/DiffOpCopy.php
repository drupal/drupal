<?php

namespace Drupal\Component\Diff\Engine;

/**
 * @todo document
 * @private
 * @subpackage DifferenceEngine
 */
class DiffOpCopy extends DiffOp {
  public $type = 'copy';

  public function __construct($orig, $closing = FALSE) {
    if (!is_array($closing)) {
      $closing = $orig;
    }
    $this->orig = $orig;
    $this->closing = $closing;
  }

  /**
   * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3337942
   */
  public function reverse() {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3337942', E_USER_DEPRECATED);
    return new DiffOpCopy($this->closing, $this->orig);
  }

}
