<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\Field\FieldFormatter\UrlPlainFormatter.
 */

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
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($this->getEntitiesToView($items) as $delta => $file) {
      $elements[$delta] = array(
        '#markup' => file_create_url($file->getFileUri()),
        '#cache' => array(
          'tags' => $file->getCacheTags(),
        ),
      );
    }

    return $elements;
  }

}
