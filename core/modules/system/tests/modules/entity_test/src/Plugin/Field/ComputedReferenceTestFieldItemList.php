<?php

namespace Drupal\entity_test\Plugin\Field;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * A computed entity reference field item list.
 */
class ComputedReferenceTestFieldItemList extends EntityReferenceFieldItemList {

  use ComputedItemListTrait;

  /**
   * Compute the list property from state.
   */
  protected function computeValue() {
    foreach (\Drupal::state()->get('entity_test_reference_computed_target_ids', []) as $delta => $id) {
      $this->list[$delta] = $this->createItem($delta, $id);
    }
  }

}
