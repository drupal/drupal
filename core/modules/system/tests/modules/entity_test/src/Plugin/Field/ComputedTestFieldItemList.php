<?php

declare(strict_types=1);

namespace Drupal\entity_test\Plugin\Field;

use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\Field\FieldItemList;

/**
 * A computed field item list.
 */
class ComputedTestFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * Compute the list property from state.
   */
  protected function computeValue() {
    // Count the number of times this method has been executed during the
    // lifecycle of an entity.
    $execution_count = \Drupal::state()->get('computed_test_field_execution', 0);
    \Drupal::state()->set('computed_test_field_execution', ++$execution_count);

    foreach (\Drupal::state()->get('entity_test_computed_field_item_list_value', []) as $delta => $item) {
      $this->list[$delta] = $this->createItem($delta, $item);
    }
  }

}
