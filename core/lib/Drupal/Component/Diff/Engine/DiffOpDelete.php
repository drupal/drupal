<?php

namespace Drupal\Component\Diff\Engine;

/**
 * @todo document
 * @private
 * @subpackage DifferenceEngine
 */
class DiffOpDelete extends DiffOp {
  public $type = 'delete';

  public function __construct($lines) {
    $this->orig = $lines;
    $this->closing = FALSE;
  }

  /**
   * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3337942
   */
  public function reverse() {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3337942', E_USER_DEPRECATED);
    return new DiffOpAdd($this->orig);
  }

}
