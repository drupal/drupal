<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\field\formatter\UrlPlainFormatter.
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

    foreach ($items as $delta => $item) {
      if ($item->isDisplayed() && $item->entity) {
        $elements[$delta] = array('#markup' => empty($item->entity) ? '' : file_create_url($item->entity->getFileUri()));
      }
    }

    return $elements;
  }

}
