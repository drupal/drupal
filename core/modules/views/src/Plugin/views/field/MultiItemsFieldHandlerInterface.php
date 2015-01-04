<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Defines a field hander which renders multiple items per row.
 */
interface MultiItemsFieldHandlerInterface extends FieldHandlerInterface {

  /**
   * Renders a single item of a row.
   *
   * @param int $count
   *   The index of the item inside the row.
   * @param mixed $item
   *   The item for the field to render.
   *
   * @return string
   *   The rendered output.
   */
  public function render_item($count, $item);

  /**
   * Gets an array of items for the field.
   *
   * @param \Drupal\views\ResultRow $values
   *   The result row object containing the values.
   *
   * @return array
   *   An array of items for the field.
   */
  public function getItems(ResultRow $values);

  /**
   * Render all items in this field together.
   *
   * @param array $items
   *   The items provided by getItems for a single row.
   *
   * @return string
   *   The rendered items.
   */
  public function renderItems($items);

}

