<?php

/**
 * @file
 * Definition of Drupal\email\Plugin\field\formatter\MailToFormatter.
 */

namespace Drupal\email\Plugin\field\formatter;

use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'email_mailto' formatter.
 *
 * @FieldFormatter(
 *   id = "email_mailto",
 *   label = @Translation("Email"),
 *   field_types = {
 *     "email"
 *   }
 * )
 */
class MailToFormatter extends FormatterBase {

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#type' => 'link',
        '#title' => $item->value,
        '#href' => 'mailto:' . $item->value,
      );
    }

    return $elements;
  }

}
