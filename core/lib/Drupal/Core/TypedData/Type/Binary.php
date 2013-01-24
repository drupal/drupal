<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Type\Binary.
 */

namespace Drupal\Core\TypedData\Type;

use Drupal\Core\TypedData\TypedData;
use InvalidArgumentException;

/**
 * The binary data type.
 *
 * The plain value of binary data is a PHP file resource, see
 * http://php.net/manual/en/language.types.resource.php. For setting the value
 * a PHP file resource or a (absolute) stream resource URI may be passed.
 */
class Binary extends TypedData {

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
   * Overrides TypedData::getValue().
   */
  public function getValue() {
    // If the value has been set by (absolute) stream resource URI, access the
    // resource now.
    if (!isset($this->handle) && isset($this->uri)) {
      $this->handle = fopen($this->uri, 'rb');
    }
    return $this->handle;
  }

  /**
   * Overrides TypedData::setValue().
   *
   * Supports a PHP file resource or a (absolute) stream resource URI as value.
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
   * Overrides TypedData::getString().
   */
  public function getString() {
    // Return the file content.
    $contents = '';
    while (!feof($this->getValue())) {
      $contents .= fread($this->handle, 8192);
    }
    return $contents;
  }
}
