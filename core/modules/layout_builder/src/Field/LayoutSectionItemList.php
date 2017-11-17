<?php

namespace Drupal\layout_builder\Field;

use Drupal\Core\Field\FieldItemList;

/**
 * Defines a item list class for layout section fields.
 *
 * @internal
 *
 * @see \Drupal\layout_builder\Plugin\Field\FieldType\LayoutSectionItem
 */
class LayoutSectionItemList extends FieldItemList implements LayoutSectionItemListInterface {

  /**
   * {@inheritdoc}
   */
  public function addItem($index, $value) {
    if ($this->get($index)) {
      $start = array_slice($this->list, 0, $index);
      $end = array_slice($this->list, $index);
      $item = $this->createItem($index, $value);
      $this->list = array_merge($start, [$item], $end);
    }
    else {
      $item = $this->appendItem($value);
    }
    return $item;
  }

}
