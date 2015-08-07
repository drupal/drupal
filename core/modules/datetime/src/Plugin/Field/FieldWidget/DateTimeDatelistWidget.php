<?php
/**
 * @file
 * Contains \Drupal\datetime\Plugin\Field\FieldWidget\DateTimeDatelistWidget.
 */

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
    return array(
      'increment' => '15',
      'date_order' => 'YMD',
      'time_type' => '24',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

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
        $date_part_order = array('year', 'month', 'day');
        break;

      case 'MDY':
        $date_part_order = array('month', 'day', 'year');
        break;

      case 'DMY':
        $date_part_order = array('day', 'month', 'year');
        break;
    }
    switch ($time_type) {
       case '24':
         $date_part_order = array_merge($date_part_order, array('hour', 'minute'));
         break;

       case '12':
         $date_part_order = array_merge($date_part_order, array('hour', 'minute', 'ampm'));
         break;

       case 'none':
         break;
    }

    $element['value'] = array(
      '#type' => 'datelist',
      '#date_increment' => $increment,
      '#date_part_order'=> $date_part_order,
    ) + $element['value'];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['date_order'] = array(
      '#type' => 'select',
      '#title' => t('Date part order'),
      '#default_value' => $this->getSetting('date_order'),
      '#options' => array('MDY' => t('Month/Day/Year'), 'DMY' => t('Day/Month/Year'), 'YMD' => t('Year/Month/Day')),
    );

    if ($this->getFieldSetting('datetime_type') == 'datetime') {
      $element['time_type'] = array(
        '#type' => 'select',
        '#title' => t('Time type'),
        '#default_value' => $this->getSetting('time_type'),
        '#options' => array('24' => t('24 hour time'), '12' => t('12 hour time')),
      );

      $element['increment'] = [
        '#type' => 'select',
        '#title' => t('Time increments'),
        '#default_value' => $this->getSetting('increment'),
        '#options' => [
          1 => t('1 minute'),
          5 => t('5 minute'),
          10 => t('10 minute'),
          15 => t('15 minute'),
          30 => t('30 minute'),
        ],
      ];
    }
    else {
      $element['time_type'] = array(
        '#type' => 'hidden',
        '#value' => 'none',
      );

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
    $summary = array();

    $summary[] = t('Date part order: !order', array('!order' => $this->getSetting('date_order')));
    if ($this->getFieldSetting('datetime_type') == 'datetime') {
      $summary[] = t('Time type: !time_type', array('!time_type' => $this->getSetting('time_type')));
      $summary[] = t('Time increments: !increment', array('!increment' => $this->getSetting('increment')));
    }

    return $summary;
  }

}
