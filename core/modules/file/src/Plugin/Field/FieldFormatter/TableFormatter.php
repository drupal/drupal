<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\Field\FieldFormatter\TableFormatter.
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

      $header = array(t('Attachment'), t('Size'));
      $rows = array();
      foreach ($items as $delta => $item) {
        if ($item->isDisplayed() && $item->entity) {
          $rows[] = array(
            array(
              'data' => array(
                '#theme' => 'file_link',
                '#file' => $item->entity,
              ),
            ),
            array('data' => format_size($item->entity->getSize())),
          );
        }
      }

      $elements[0] = array();
      if (!empty($rows)) {
        $elements[0] = array(
          '#theme' => 'table__file_formatter_table',
          '#header' => $header,
          '#rows' => $rows,
        );
      }
    }

    return $elements;
  }

}
