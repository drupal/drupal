<?php

namespace Drupal\entity_test\TypedData;

use Drupal\Core\TypedData\TypedData;

/**
 * A computed property for test strings.
 */
class ComputedString extends TypedData {

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    $item = $this->getParent();
    $computed_value = "Computed! " . $item->get('value')->getString();

    return $computed_value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCastedValue() {
    return $this->getString();
  }

}
