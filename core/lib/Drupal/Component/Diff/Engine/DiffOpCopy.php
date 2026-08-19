<?php

namespace Drupal\Component\Diff\Engine;

/**
 * A diff operation to copy lines.
 *
 * @private
 * @subpackage DifferenceEngine
 */
class DiffOpCopy extends DiffOp {

  /**
   * {@inheritdoc}
   */
  public $type = 'copy';

  public function __construct($orig, $closing = FALSE) {
    if (!is_array($closing)) {
      $closing = $orig;
    }
    $this->orig = $orig;
    $this->closing = $closing;
  }

}
