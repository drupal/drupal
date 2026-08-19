<?php

namespace Drupal\Component\Diff\Engine;

/**
 * A diff operation.
 *
 * @private
 * @subpackage DifferenceEngine
 */
class DiffOp {

  /**
   * The type of operation.
   *
   * @var string
   */
  public $type;

  /**
   * An array of the original lines.
   *
   * @var string[]
   */
  public $orig;

  /**
   * An array of the new lines.
   *
   * @var string[]
   */
  public $closing;

}
