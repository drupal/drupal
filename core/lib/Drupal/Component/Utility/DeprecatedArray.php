<?php

namespace Drupal\Component\Utility;

/**
 * An array that triggers a deprecation warning when accessed.
 */
class DeprecatedArray extends \ArrayObject {

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
    $this->message = $message;
    parent::__construct($values);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset) {
    @trigger_error($this->message, E_USER_DEPRECATED);
    return parent::offsetExists($offset);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($offset) {
    @trigger_error($this->message, E_USER_DEPRECATED);
    return parent::offsetGet($offset);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value) {
    @trigger_error($this->message, E_USER_DEPRECATED);
    parent::offsetSet($offset, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset) {
    @trigger_error($this->message, E_USER_DEPRECATED);
    parent::offsetUnset($offset);
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    @trigger_error($this->message, E_USER_DEPRECATED);
    return parent::getIterator();
  }

  /**
   * {@inheritdoc}
   */
  public function unserialize($serialized) {
    @trigger_error($this->message, E_USER_DEPRECATED);
    parent::unserialize($serialized);
  }

  /**
   * {@inheritdoc}
   */
  public function serialize() {
    @trigger_error($this->message, E_USER_DEPRECATED);
    return parent::serialize();
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    @trigger_error($this->message, E_USER_DEPRECATED);
    return parent::count();
  }

}
