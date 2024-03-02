<?php

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\PrimitiveBase;
use Drupal\Core\TypedData\Type\BinaryInterface;

/**
 * The binary data type.
 *
 * The plain value of binary data is a PHP file resource, see
 * http://php.net/manual/language.types.resource.php. For setting the value
 * a PHP file resource or an (absolute) stream resource URI may be passed.
 */
#[DataType(
  id: "binary",
  label: new TranslatableMarkup("Binary")
)]
class BinaryData extends PrimitiveBase implements BinaryInterface {

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
   * {@inheritdoc}
   */
  public function getValue() {
    // If the value has been set by (absolute) stream resource URI, access the
    // resource now.
    if (!isset($this->handle) && isset($this->uri)) {
      $this->handle = is_readable($this->uri) ? fopen($this->uri, 'rb') : FALSE;
    }
    return $this->handle;
  }

  /**
   * Overrides TypedData::setValue().
   *
   * Supports a PHP file resource or an (absolute) stream resource URI as value.
   */
  public function setValue($value, $notify = TRUE) {
    if (!isset($value)) {
      $this->handle = NULL;
      $this->uri = NULL;
    }
    elseif (is_string($value)) {
      // Note: For performance reasons we store the given URI and access the
      // resource upon request. See BinaryData::getValue()
      $this->uri = $value;
      $this->handle = NULL;
    }
    else {
      $this->handle = $value;
    }
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getString() {
    // Return the file content.
    $contents = '';
    while (!feof($this->getValue())) {
      $contents .= fread($this->handle, 8192);
    }
    return $contents;
  }

  /**
   * {@inheritdoc}
   */
  public function getCastedValue() {
    return $this->getValue();
  }

}
