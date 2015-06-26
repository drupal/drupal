<?php

/**
 * @file
 * Contains \Drupal\Component\Diff\Engine\DiffOpDelete.
 */

namespace Drupal\Component\Diff\Engine;

/**
 * @todo document
 * @private
 * @subpackage DifferenceEngine
 */
class DiffOpDelete extends DiffOp {
  var $type = 'delete';

  public function __construct($lines) {
    $this->orig = $lines;
    $this->closing = FALSE;
  }

  public function reverse() {
    return new DiffOpAdd($this->orig);
  }
}
