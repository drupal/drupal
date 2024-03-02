<?php

namespace Drupal\Core\Field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'boolean' formatter.
 */
#[FieldFormatter(
  id: 'boolean',
  label: new TranslatableMarkup('Boolean'),
  field_types: [
    'boolean',
  ],
)]
class BooleanFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [];

    // Fall back to field settings by default.
    $settings['format'] = 'default';
    $settings['format_custom_false'] = '';
    $settings['format_custom_true'] = '';

    return $settings;
  }

  /**
   * Gets the available format options.
   *
   * @return array|string
   *   A list of output formats. Each entry is keyed by the machine name of the
   *   format. The value is an array, of which the first item is the result for
   *   boolean TRUE, the second is for boolean FALSE. The value can be also an
   *   array, but this is just the case for the custom format.
   */
  protected function getOutputFormats() {
    $formats = [
      'default' => [$this->getFieldSetting('on_label'), $this->getFieldSetting('off_label')],
      'yes-no' => [$this->t('Yes'), $this->t('No')],
      'true-false' => [$this->t('True'), $this->t('False')],
      'on-off' => [$this->t('On'), $this->t('Off')],
      'enabled-disabled' => [$this->t('Enabled'), $this->t('Disabled')],
      'boolean' => [1, 0],
      'unicode-yes-no' => ['✔', '✖'],
      'custom' => $this->t('Custom'),
    ];

    return $formats;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $formats = [];
    foreach ($this->getOutputFormats() as $format_name => $format) {
      if (is_array($format)) {
        if ($format_name == 'default') {
          $formats[$format_name] = $this->t('Field settings (@on_label / @off_label)', ['@on_label' => $format[0], '@off_label' => $format[1]]);
        }
        else {
          $formats[$format_name] = $this->t('@on_label / @off_label', ['@on_label' => $format[0], '@off_label' => $format[1]]);
        }
      }
      else {
        $formats[$format_name] = $format;
      }
    }

    $field_name = $this->fieldDefinition->getName();
    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Output format'),
      '#default_value' => $this->getSetting('format'),
      '#options' => $formats,
    ];
    $form['format_custom_true'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom output for TRUE'),
      '#default_value' => $this->getSetting('format_custom_true'),
      '#states' => [
        'visible' => [
          'select[name="fields[' . $field_name . '][settings_edit_form][settings][format]"]' => ['value' => 'custom'],
        ],
      ],
    ];
    $form['format_custom_false'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom output for FALSE'),
      '#default_value' => $this->getSetting('format_custom_false'),
      '#states' => [
        'visible' => [
          'select[name="fields[' . $field_name . '][settings_edit_form][settings][format]"]' => ['value' => 'custom'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $setting = $this->getSetting('format');

    if ($setting == 'custom') {
      $summary[] = $this->t('Custom text: @true_label / @false_label', [
        '@true_label' => $this->getSetting('format_custom_true'),
        '@false_label' => $this->getSetting('format_custom_false'),
      ]);
    }
    else {
      $formats = $this->getOutputFormats();
      $summary[] = $this->t('Display: @true_label / @false_label', [
        '@true_label' => $formats[$setting][0],
        '@false_label' => $formats[$setting][1],
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $formats = $this->getOutputFormats();

    foreach ($items as $delta => $item) {
      $format = $this->getSetting('format');

      if ($format == 'custom') {
        $elements[$delta] = ['#markup' => $item->value ? $this->getSetting('format_custom_true') : $this->getSetting('format_custom_false')];
      }
      else {
        $elements[$delta] = ['#markup' => $item->value ? $formats[$format][0] : $formats[$format][1]];
      }
    }

    return $elements;
  }

}
