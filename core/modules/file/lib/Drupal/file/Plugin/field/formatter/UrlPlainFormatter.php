<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\field\formatter\UrlPlainFormatter.
 */

namespace Drupal\file\Plugin\field\formatter;

use Drupal\field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'file_url_plain' formatter.
 *
 * @FieldFormatter(
 *   id = "file_url_plain",
 *   module = "file",
 *   label = @Translation("URL to file"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class UrlPlainFormatter extends FileFormatterBase {

  /**
   * Implements \Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      if ($item['display'] && $item['entity']) {
        $elements[$delta] = array('#markup' => empty($item['entity']) ? '' : file_create_url($item['entity']->getFileUri()));
      }
    }

    return $elements;
  }

}
