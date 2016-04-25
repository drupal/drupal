<?php

namespace Drupal\Component\Diff\Engine;

/**
 * @todo document
 * @private
 * @subpackage DifferenceEngine
 */
class DiffOpAdd extends DiffOp {
  var $type = 'add';

  public function __construct($lines) {
    $this->closing = $lines;
    $this->orig = FALSE;
  }

  public function reverse() {
    return new DiffOpDelete($this->closing);
  }
}
