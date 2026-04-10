<?php

namespace Drupal\Component\Utility;

@trigger_error('The ' . __NAMESPACE__ . '\ToStringTrait is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Implement the __toString() method directly, exception handling is no longer required. See https://www.drupal.org/node/3548961', E_USER_DEPRECATED);

/**
 * Wraps __toString in a trait to avoid some fatal errors.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Implement
 *   the __toString() method directly, exception handling is no longer required.
 *
 * @see https://www.drupal.org/node/3548961
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
      trigger_error(get_class($e) . ' thrown while calling __toString on a ' . static::class . ' object in ' . $e->getFile() . ' on line ' . $e->getLine() . ': ' . $e->getMessage(), E_USER_WARNING);
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
