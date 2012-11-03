<?php

/**
 * @file
 * Definition of Drupal\Core\Template\TwigReference.
 */

namespace Drupal\Core\Template;

/**
 * A class used to pass variables by reference while they are used in twig.
 *
 * This is done by saving a reference to the original render array within a
 * TwigReference via the setReference() method like this:
 * @code
 * $obj = new TwigReference();
 * $obj->setReference($variable);
 * @endcode
 *
 * When a TwigReference is accessed via the offsetGet method the resulting
 * reference is again wrapped within a TwigReference. Therefore references to
 * render arrays within render arrays are also retained.
 *
 * To unwrap TwigReference objects the reference can be retrieved out of the
 * object by calling the getReference() method like this:
 * @code
 * $variable = &$obj->getReference();
 * @endcode
 * This allows render(), hide() and show() to access the original variable and
 * change it. The process of unwrapping and passing by reference to this
 * functions is done transparently by the TwigReferenceFunctions helper class.
 *
 * @see TwigReferenceFunction
 * @see TwigReferenceFunctions
 */
class TwigReference extends \ArrayObject {

  /**
   * Holds an internal reference to the original array.
   *
   * @var array
   */
  protected $writableRef = array();

  /**
   * Constructs a \Drupal\Core\Template\TwigReference object.
   *
   * The argument to the constructor is ignored as it is not safe that this will
   * always be a reference.
   *
   * To set a reference use:
   * @code
   * $obj = new TwigReference();
   * $obj->setReference($variable);
   * @endcode
   *
   * @param $array
   *   The array parameter is ignored and not passed to the parent
   */
  public function __construct($array = NULL) {
    parent::__construct();
  }

  /**
   * Sets a reference in the internal storage.
   *
   * @param $array
   *   The array to set as internal reference.
   */
  public function setReference(&$array) {
    $this->exchangeArray($array);
    $this->writableRef = &$array;
  }

  /**
   * Gets a reference to the internal storage.
   *
   * Should be called like:
   * @code
   * $reference = &$obj->getReference();
   * @endcode
   *
   * @return
   *   Returns the stored internal reference.
   */
  public function &getReference() {
    return $this->writableRef;
  }

  /**
   * Sets offset in internal reference and internal storage to value.
   *
   * This is just for completeness, but should never be used, because
   * twig cannot set properties and should not.
   *
   * @link http://php.net/manual/en/arrayaccess.offsetset.php
   * @param mixed $offset
   *   The offset to assign the value to.
   * @param mixed $value
   *   The value to set.
   */
  public function offsetSet($offset, $value) {
    $this->writableRef[$offset] = $value;
    parent::offsetSet($offset, $value);
  }

  /**
   * Retrieves offset from internal reference.
   *
   * In case of a render array, it is wrapped again within a TwigReference
   * object.
   *
   * @param mixed $offset
   *   The offset to retrieve.
   *
   * @return mixed
   *   Returns a TwigReference object wrapping the array if the retrieved offset
   *   is a complex array (i.e. not an attribute). Else it returns the retrived
   *   offset directly.
   */
  public function offsetGet($offset) {
    if (!is_array($this->writableRef[$offset]) || $offset[0] == '#') {
      return $this->writableRef[$offset];
    }

    // Wrap the returned array in a new TwigReference.
    $x = clone $this; // clone is faster than new
    $x->setReference($this->writableRef[$offset]);
    return $x;
  }
}
