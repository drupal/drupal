<?php

/**
 * @file
 * Contains \Drupal\telephone\Plugin\field\formatter\TelephoneLinkFormatter.
 */

namespace Drupal\telephone\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'telephone_link' formatter.
 *
 * @FieldFormatter(
 *   id = "telephone_link",
 *   label = @Translation("Telephone link"),
 *   field_types = {
 *     "telephone"
 *   },
 *   settings = {
 *     "title" = ""
 *   }
 * )
 */
class TelephoneLinkFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $elements['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title to replace basic numeric telephone number display.'),
      '#default_value' => $this->getSetting('title'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $settings = $this->getSettings();

    if (!empty($settings['title'])) {
      $summary[] = t('Link using text: @title', array('@title' => $settings['title']));
    }
    else {
      $summary[] = t('Link using provided telephone number.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $element = array();
    $title_setting = $this->getSetting('title');

    foreach ($items as $delta => $item) {
      // Render each element as link.
      $element[$delta] = array(
        '#type' => 'link',
        // Use custom title if available, otherwise use the telephone number
        // itself as title.
        '#title' => $title_setting ?: $item->value,
        // Prepend 'tel:' to the telephone number.
        '#href' => 'tel:' . rawurlencode(preg_replace('/\s+/', '', $item->value)),
        '#options' => array('external' => TRUE),
      );
    }

    return $element;
  }
}
