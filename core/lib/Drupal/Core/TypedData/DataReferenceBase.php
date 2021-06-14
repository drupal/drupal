<?php

namespace Drupal\Core\TypedData;

/**
 * Base class for typed data references.
 *
 * Data types based on this base class need to be named
 * "{TARGET_TYPE}_reference", whereas {TARGET_TYPE} is the referenced data type.
 * For example, an entity reference data type would have to be named
 * "entity_reference".
 * Beside that, implementing classes have to implement at least
 * \Drupal\Core\TypedData\DataReferenceInterface::getTargetIdentifier().
 *
 * @see \Drupal\Core\TypedData\DataReferenceDefinition
 */
abstract class DataReferenceBase extends TypedData implements DataReferenceInterface {

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
    $this->target = $this->getTypedDataManager()->create($this->definition->getTargetDefinition(), $value);
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
