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

  public function reverse() {
    return new DiffOpCopy($this->closing, $this->orig);
  }

}
