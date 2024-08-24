<?php

declare(strict_types=1);

namespace Drupal\entity_test\Plugin\Field;

use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\Field\FieldItemList;

/**
 * A computed field item list for a bundle field.
 */
class ComputedTestBundleFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * Compute the list property from state.
   */
  protected function computeValue() {
    foreach (\Drupal::state()->get('entity_test_comp_bund_fld_item_list_value', []) as $delta => $item) {
      $this->list[$delta] = $this->createItem($delta, $item);
    }
  }

}
