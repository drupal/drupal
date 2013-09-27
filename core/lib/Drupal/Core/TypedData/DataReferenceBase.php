<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\DataReferenceBase.
 */

namespace Drupal\Core\TypedData;

/**
 * Base class for typed data references.
 *
 * Implementing classes have to implement at least
 * \Drupal\Core\TypedData\DataReferenceInterface::getTargetDefinition() and
 * \Drupal\Core\TypedData\DataReferenceInterface::getTargetIdentifier().
 */
abstract class DataReferenceBase extends TypedData implements DataReferenceInterface  {

  /**
   * The referenced data.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface
   */
  protected $target;

  /**
   * {@inheritdoc}
   */
  public function getTarget() {
    return $this->target;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if ($target = $this->getTarget()) {
      return $target->getValue();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->target = \Drupal::typedData()->create($this->getTargetDefinition(), $value);
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getString() {
    return (string) $this->getType() . ':' . $this->getTargetIdentifier();
  }
}
