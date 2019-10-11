<?php

namespace Drupal\Component\Utility;

/**
 * An array that triggers a deprecation warning when accessed.
 */
class DeprecatedArray implements \ArrayAccess {

  /**
   * The array values.
   *
   * @var array
   */
  protected $values = [];

  /**
   * The deprecation message.
   *
   * @var string
   */
  protected $message;

  /**
   * DeprecatedArray constructor.
   *
   * @param array $values
   *   The array values.
   * @param $message
   *   The deprecation message.
   */
  public function __construct(array $values, $message) {
    $this->values = $values;
    $this->message = $message;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset) {
    @trigger_error($this->message, E_USER_DEPRECATED);
    return isset($this->values[$offset]);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($offset) {
    @trigger_error($this->message, E_USER_DEPRECATED);
    return $this->values[$offset] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value) {
    @trigger_error($this->message, E_USER_DEPRECATED);
    return $this->values[$offset] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset) {
    @trigger_error($this->message, E_USER_DEPRECATED);
    unset($this->values[$offset]);
  }

}
