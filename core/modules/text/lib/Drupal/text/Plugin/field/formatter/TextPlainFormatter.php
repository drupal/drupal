<?php

/**
 * @file
 * Definition of Drupal\text\Plugin\field\formatter\TextPlainFormatter.
 */

namespace Drupal\text\Plugin\field\formatter;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'text_plain' formatter.
 *
 * @Plugin(
 *   id = "text_plain",
 *   module = "text",
 *   label = @Translation("Plain text"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   },
 *   edit = {
 *     "editor" = "direct"
 *   }
 * )
 */
class TextPlainFormatter extends FormatterBase {

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array('#markup' => strip_tags($item['value']));
    }

    return $elements;
  }

}
