<?php

namespace Drupal\telephone\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'telephone_link' formatter.
 */
#[FieldFormatter(
  id: 'telephone_link',
  label: new TranslatableMarkup('Telephone link'),
  field_types: [
    'telephone',
  ],
)]
class TelephoneLinkFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'title' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title to replace basic numeric telephone number display'),
      '#default_value' => $this->getSetting('title'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();

    if (!empty($settings['title'])) {
      $summary[] = $this->t('Link using text: @title', ['@title' => $settings['title']]);
    }
    else {
      $summary[] = $this->t('Link using provided telephone number.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $title_setting = $this->getSetting('title');

    foreach ($items as $delta => $item) {
      // If the telephone number is 5 or less digits, parse_url() will think
      // it's a port number rather than a phone number which causes the link
      // formatter to throw an InvalidArgumentException. Avoid this by inserting
      // a dash (-) after the first digit - RFC 3966 defines the dash as a
      // visual separator character and so will be removed before the phone
      // number is used. See https://bugs.php.net/bug.php?id=70588 for more.
      // While the bug states this only applies to numbers <= 65535, a 5 digit
      // number greater than 65535 will cause parse_url() to return FALSE so
      // we need the work around on any 5 digit (or less) number.
      // First we strip whitespace so we're counting actual digits.
      $phone_number = preg_replace('/\s+/', '', $item->value);
      if (strlen($phone_number) <= 5) {
        $phone_number = substr_replace($phone_number, '-', 1, 0);
      }

      // Render each element as link.
      $element[$delta] = [
        '#type' => 'link',
        // Use custom title if available, otherwise use the telephone number
        // itself as title.
        '#title' => $title_setting ?: $item->value,
        // Prepend 'tel:' to the telephone number.
        '#url' => Url::fromUri('tel:' . rawurlencode($phone_number)),
        '#options' => ['external' => TRUE],
      ];

      if (!empty($item->_attributes)) {
        $element[$delta]['#options'] += ['attributes' => []];
        $element[$delta]['#options']['attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }

    return $element;
  }

}
