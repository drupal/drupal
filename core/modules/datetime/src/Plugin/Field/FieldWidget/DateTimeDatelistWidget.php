<?php

namespace Drupal\datetime\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'datetime_datelist' widget.
 *
 * @FieldWidget(
 *   id = "datetime_datelist",
 *   label = @Translation("Select list"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class DateTimeDatelistWidget extends DateTimeWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'increment' => '15',
      'date_order' => 'YMD',
      'time_type' => '24',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Wrap all of the select elements with a fieldset.
    $element['#theme_wrappers'][] = 'fieldset';

    $date_order = $this->getSetting('date_order');

    if ($this->getFieldSetting('datetime_type') == 'datetime') {
      $time_type = $this->getSetting('time_type');
      $increment = $this->getSetting('increment');
    }
    else {
      $time_type = '';
      $increment = '';
    }

    // Set up the date part order array.
    switch ($date_order) {
      case 'YMD':
        $date_part_order = ['year', 'month', 'day'];
        break;

      case 'MDY':
        $date_part_order = ['month', 'day', 'year'];
        break;

      case 'DMY':
        $date_part_order = ['day', 'month', 'year'];
        break;
    }
    switch ($time_type) {
      case '24':
        $date_part_order = array_merge($date_part_order, ['hour', 'minute']);
        break;

      case '12':
        $date_part_order = array_merge($date_part_order, ['hour', 'minute', 'ampm']);
        break;

      case 'none':
        break;
    }

    $element['value'] = [
      '#type' => 'datelist',
      '#date_increment' => $increment,
      '#date_part_order' => $date_part_order,
    ] + $element['value'];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['date_order'] = [
      '#type' => 'select',
      '#title' => $this->t('Date part order'),
      '#default_value' => $this->getSetting('date_order'),
      '#options' => [
        'MDY' => $this->t('Month/Day/Year'),
        'DMY' => $this->t('Day/Month/Year'),
        'YMD' => $this->t('Year/Month/Day'),
      ],
    ];

    if ($this->getFieldSetting('datetime_type') == 'datetime') {
      $element['time_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Time type'),
        '#default_value' => $this->getSetting('time_type'),
        '#options' => ['24' => $this->t('24 hour time'), '12' => $this->t('12 hour time')],
      ];

      $element['increment'] = [
        '#type' => 'select',
        '#title' => $this->t('Time increments'),
        '#default_value' => $this->getSetting('increment'),
        '#options' => [
          1 => $this->t('1 minute'),
          5 => $this->t('5 minute'),
          10 => $this->t('10 minute'),
          15 => $this->t('15 minute'),
          30 => $this->t('30 minute'),
        ],
      ];
    }
    else {
      $element['time_type'] = [
        '#type' => 'hidden',
        '#value' => 'none',
      ];

      $element['increment'] = [
        '#type' => 'hidden',
        '#value' => $this->getSetting('increment'),
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Date part order: @order', ['@order' => $this->getSetting('date_order')]);
    if ($this->getFieldSetting('datetime_type') == 'datetime') {
      $summary[] = $this->t('Time type: @time_type', ['@time_type' => $this->getSetting('time_type')]);
      $summary[] = $this->t('Time increments: @increment', ['@increment' => $this->getSetting('increment')]);
    }

    return $summary;
  }

}
