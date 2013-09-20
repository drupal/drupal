<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\field\formatter\GenericFileFormatter.
 */

namespace Drupal\file\Plugin\field\formatter;

use Drupal\field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Field\FieldInterface;

/**
 * Plugin implementation of the 'file_default' formatter.
 *
 * @FieldFormatter(
 *   id = "file_default",
 *   label = @Translation("Generic file"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class GenericFileFormatter extends FileFormatterBase {

  /**
   * Implements \Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(FieldInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      if ($item->display && $item->entity) {
        $elements[$delta] = array(
          '#theme' => 'file_link',
          '#file' => $item->entity,
          '#description' => $item->description,
        );
        // Pass field item attributes to the theme function.
        if (isset($item->_attributes)) {
          $elements[$delta] += array('#attributes' => array());
          $elements[$delta]['#attributes'] += $item->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and should not be rendered in the field template.
          unset($item->_attributes);
        }
      }
    }

    return $elements;
  }

}
