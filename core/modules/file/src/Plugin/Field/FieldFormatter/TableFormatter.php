<?php

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
class TableFormatter extends DescriptionAwareFileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    if ($files = $this->getEntitiesToView($items, $langcode)) {
      $header = [$this->t('Attachment'), $this->t('Size')];
      $rows = [];
      foreach ($files as $file) {
        $item = $file->_referringItem;
        $rows[] = [
          [
            'data' => [
              '#theme' => 'file_link',
              '#file' => $file,
              '#description' => $this->getSetting('use_description_as_link_text') ? $item->description : NULL,
              '#cache' => [
                'tags' => $file->getCacheTags(),
              ],
            ],
          ],
          ['data' => $file->getSize() !== NULL ? format_size($file->getSize()) : $this->t('Unknown')],
        ];
      }

      $elements[0] = [];
      if (!empty($rows)) {
        $elements[0] = [
          '#theme' => 'table__file_formatter_table',
          '#header' => $header,
          '#rows' => $rows,
        ];
      }
    }

    return $elements;
  }

}
