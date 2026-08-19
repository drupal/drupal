<?php

namespace Drupal\Component\Diff\Engine;

/**
 * A diff operation to change lines.
 *
 * @private
 * @subpackage DifferenceEngine
 */
class DiffOpChange extends DiffOp {

  /**
   * {@inheritdoc}
   */
  public $type = 'change';

  public function __construct($orig, $closing) {
    $this->orig = $orig;
    $this->closing = $closing;
  }

}
