<?php
/**
 * @file
 * Contains \Drupal\Core\Datetime\Plugin\Field\FieldWidget\TimestampDatetimeWidget.
 */

namespace Drupal\Core\Datetime\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Element\Datetime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'datetime timestamp' widget.
 *
 * @FieldWidget(
 *   id = "datetime_timestamp",
 *   label = @Translation("Datetime Timestamp"),
 *   field_types = {
 *     "timestamp",
 *     "created",
 *   }
 * )
 */
class TimestampDatetimeWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();
    $default_value = isset($items[$delta]->value) ? DrupalDateTime::createFromTimestamp($items[$delta]->value) : '';
    $element['value'] = $element + array(
      '#type' => 'datetime',
      '#default_value' => $default_value,
      '#date_year_range' => '1902:2037',
    );
    $element['value']['#description'] = $this->t('Format: %format. Leave blank to use the time of form submission.', array('%format' => Datetime::formatExample($date_format . ' ' . $time_format)));

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      // @todo The structure is different whether access is denied or not, to
      //   be fixed in https://www.drupal.org/node/2326533.
      if (isset($item['value']) && $item['value'] instanceof DrupalDateTime) {
        $date = $item['value'];
      }
      elseif (isset($item['value']['object']) && $item['value']['object'] instanceof DrupalDateTime) {
        $date = $item['value']['object'];
      }
      else {
        $date = new DrupalDateTime();
      }
      $item['value'] = $date->getTimestamp();
    }
    return $values;
  }

}
