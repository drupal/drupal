<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\field\formatter\UrlPlainFormatter.
 */

namespace Drupal\file\Plugin\field\formatter;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'file_url_plain' formatter.
 *
 * @Plugin(
 *   id = "file_url_plain",
 *   module = "file",
 *   label = @Translation("URL to file"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class UrlPlainFormatter extends FormatterBase {

  /**
   * Implements \Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array('#markup' => empty($item['uri']) ? '' : file_create_url($item['uri']));
    }

    return $elements;
  }

}
