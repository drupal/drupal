<?php

/**
 * @file
 * Contains \Drupal\Component\Diff\Engine\DiffOpChange.
 */

namespace Drupal\Component\Diff\Engine;

/**
 * @todo document
 * @private
 * @subpackage DifferenceEngine
 */
class DiffOpChange extends DiffOp {
  var $type = 'change';

  public function __construct($orig, $closing) {
    $this->orig = $orig;
    $this->closing = $closing;
  }

  public function reverse() {
    return new DiffOpChange($this->closing, $this->orig);
  }
}
