<?php

namespace Drupal\Component\Utility;

/**
 * Wraps __toString in a trait to avoid some fatals.
 */
trait ToStringTrait {

  /**
   * Implements the magic __toString() method.
   */
  public function __toString() {
    try {
      return (string) $this->render();
    }
    catch (\Exception $e) {
      // User errors in __toString() methods are considered fatal in the Drupal
      // error handler.
      trigger_error(get_class($e) . ' thrown while calling __toString on a ' . get_class($this) . ' object in ' . $e->getFile() . ' on line ' . $e->getLine() . ': ' . $e->getMessage(), E_USER_ERROR);
      // In case we are using another error handler that did not fatal on the
      // E_USER_ERROR, we terminate execution. However, for test purposes allow
      // a return value.
      return $this->_die();
    }
  }

  /**
   * For test purposes, wrap die() in an overridable method.
   */
  protected function _die() {
    die();
  }

  /**
   * Renders the object as a string.
   *
   * @return string|object
   *   The rendered string or an object implementing __toString().
   */
  abstract public function render();

}
