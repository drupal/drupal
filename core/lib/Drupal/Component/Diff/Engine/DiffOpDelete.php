<?php

namespace Drupal\Component\Diff\Engine;

/**
 * A diff operation to delete lines.
 *
 * @private
 * @subpackage DifferenceEngine
 */
class DiffOpDelete extends DiffOp {

  /**
   * {@inheritdoc}
   */
  public $type = 'delete';

  public function __construct($lines) {
    $this->orig = $lines;
    $this->closing = FALSE;
  }

}
