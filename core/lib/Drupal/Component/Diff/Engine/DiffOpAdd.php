<?php

namespace Drupal\Component\Diff\Engine;

/**
 * A diff operation to add lines.
 *
 * @private
 * @subpackage DifferenceEngine
 */
class DiffOpAdd extends DiffOp {

  /**
   * {@inheritdoc}
   */
  public $type = 'add';

  public function __construct($lines) {
    $this->closing = $lines;
    $this->orig = FALSE;
  }

}
