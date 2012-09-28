<?php

/**
 * @file
 * Definition of Drupal\Core\TypedData\Type\Binary.
 */

namespace Drupal\Core\TypedData\Type;

use Drupal\Core\TypedData\TypedDataInterface;
use InvalidArgumentException;

/**
 * The binary data type.
 *
 * The plain value of binary data is a PHP file resource, see
 * http://php.net/manual/en/language.types.resource.php. For setting the value
 * a PHP file resource or a (absolute) stream resource URI may be passed.
 */
class Binary extends TypedData implements TypedDataInterface {

  /**
   * The file resource URI.
   *
   * @var string
   */
  protected $uri;

  /**
   * A generic file resource handle.
   *
   * @var resource
   */
  public $handle = NULL;

  /**
   * Implements TypedDataInterface::getValue().
   */
  public function getValue() {
    if (!isset($this->handle) && isset($this->uri)) {
      $this->handle = fopen($this->uri, 'rb');
    }
    return $this->handle;
  }

  /**
   * Implements TypedDataInterface::setValue().
   */
  public function setValue($value) {
    if (!isset($value)) {
      $this->handle = NULL;
      $this->uri = NULL;
    }
    elseif (is_resource($value)) {
      $this->handle = $value;
    }
    elseif (is_string($value)) {
      // Note: For performance reasons we store the given URI and access the
      // resource upon request. See Binary::getValue()
      $this->uri = $value;
    }
    else {
      throw new InvalidArgumentException("Invalid value for binary data given.");
    }
  }

  /**
   * Implements TypedDataInterface::getString().
   */
  public function getString() {
    $contents = '';
    while (!feof($this->getValue())) {
      $contents .= fread($this->handle, 8192);
    }
    return $contents;
  }

  /**
   * Implements TypedDataInterface::validate().
   */
  public function validate() {
    // TODO: Implement validate() method.
  }
}
