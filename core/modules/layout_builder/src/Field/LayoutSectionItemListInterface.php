<?php

namespace Drupal\layout_builder\Field;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Defines a item list class for layout section fields.
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 *
 * @see \Drupal\layout_builder\Plugin\Field\FieldType\LayoutSectionItem
 */
interface LayoutSectionItemListInterface extends FieldItemListInterface {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\layout_builder\Field\LayoutSectionItemInterface|null
   *   The layout section item, if it exists.
   */
  public function get($index);

  /**
   * Adds a new item to the list.
   *
   * If an item exists at the given index, the item at that position and others
   * after it are shifted backward.
   *
   * @param int $index
   *   The position of the item in the list.
   * @param mixed $value
   *   The value of the item to be stored at the specified position.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The item that was appended.
   *
   * @todo Move to \Drupal\Core\TypedData\ListInterface directly in
   *   https://www.drupal.org/node/2907417.
   */
  public function addItem($index, $value);

}
