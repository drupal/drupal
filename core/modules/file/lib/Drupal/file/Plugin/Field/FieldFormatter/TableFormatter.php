<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\field\formatter\TableFormatter.
 */

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'file_table' formatter.
 *
 * @FieldFormatter(
 *   id = "file_table",
 *   label = @Translation("Table of files"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class TableFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    if (!$items->isEmpty()) {
      // Display all values in a single element.
      $elements[0] = array(
        '#theme' => 'file_formatter_table',
        '#items' => $items,
      );
    }

    return $elements;
  }

}
