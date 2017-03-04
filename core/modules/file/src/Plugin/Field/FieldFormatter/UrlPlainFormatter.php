<?php

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'file_url_plain' formatter.
 *
 * @FieldFormatter(
 *   id = "file_url_plain",
 *   label = @Translation("URL to file"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class UrlPlainFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $elements[$delta] = [
        '#markup' => file_url_transform_relative(file_create_url($file->getFileUri())),
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
      ];
    }

    return $elements;
  }

}
