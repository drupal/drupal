<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\field\formatter\GenericFileFormatter.
 */

namespace Drupal\file\Plugin\field\formatter;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'file_default' formatter.
 *
 * @Plugin(
 *   id = "file_default",
 *   module = "file",
 *   label = @Translation("Generic file"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class GenericFileFormatter extends FormatterBase {

  /**
   * Implements \Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#theme' => 'file_link',
        '#file' => file_load($item['fid']),
        '#description' => $item['description'],
      );
    }

    return $elements;
  }

}
