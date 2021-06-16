<?php

namespace Drupal\Core\Field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;

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
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'link',
        '#title' => $item->value,
        '#url' => Url::fromUri('mailto:' . $item->value),
      ];
    }

    return $elements;
  }

}
