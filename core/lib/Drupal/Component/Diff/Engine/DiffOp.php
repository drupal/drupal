<?php

namespace Drupal\Component\Diff\Engine;

/**
 * @todo document
 * @private
 * @subpackage DifferenceEngine
 */
class DiffOp {
  public $type;
  public $orig;
  public $closing;

  public function reverse() {
    trigger_error('pure virtual', E_USER_ERROR);
  }

  public function norig() {
    return $this->orig ? sizeof($this->orig) : 0;
  }

  public function nclosing() {
    return $this->closing ? sizeof($this->closing) : 0;
  }

}
