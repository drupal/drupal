<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\field\formatter\TableFormatter.
 */

namespace Drupal\file\Plugin\field\formatter;

use Drupal\field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Field\FieldItemListInterface;

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
   * Implements \Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    if (!$items->isEmpty()) {
      // Display all values in a single element.
      $elements[0] = array(
        '#theme' => 'file_formatter_table',
        '#items' => $items->getValue(TRUE),
      );
    }

    return $elements;
  }

}
