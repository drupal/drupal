<?php

namespace Drupal\entity_test\Plugin\Field;

use Drupal\Core\Field\FieldItemList;

/**
 * A computed field item list.
 */
class ComputedTestFieldItemList extends FieldItemList {

  /**
   * Compute the list property from state.
   */
  protected function computedListProperty() {
    foreach (\Drupal::state()->get('entity_test_computed_field_item_list_value', []) as $delta => $item) {
      $this->list[$delta] = $this->createItem($delta, $item);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($index) {
    $this->computedListProperty();
    return isset($this->list[$index]) ? $this->list[$index] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    $this->computedListProperty();
    return parent::getIterator();
  }

}
