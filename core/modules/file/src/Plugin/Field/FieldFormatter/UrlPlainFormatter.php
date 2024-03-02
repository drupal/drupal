<?php

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\FileInterface;

/**
 * Plugin implementation of the 'file_url_plain' formatter.
 */
#[FieldFormatter(
  id: 'file_url_plain',
  label: new TranslatableMarkup('URL to file'),
  field_types: [
    'file',
  ],
)]
class UrlPlainFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      assert($file instanceof FileInterface);
      $elements[$delta] = [
        '#markup' => $file->createFileUrl(),
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
      ];
    }

    return $elements;
  }

}
