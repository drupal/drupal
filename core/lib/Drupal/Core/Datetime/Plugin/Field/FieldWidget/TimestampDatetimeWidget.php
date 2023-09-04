<?php

namespace Drupal\Core\Datetime\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
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
    $parent_entity = $items->getParent()->getValue();
    $default_value = (!$parent_entity->isNew() && isset($items[$delta]->value)) ? DrupalDateTime::createFromTimestamp($items[$delta]->value) : '';
    $element['value'] = $element + [
      '#type' => 'datetime',
      '#default_value' => $default_value,
      '#date_year_range' => '1902:2037',
    ];

    $element['value']['#description'] = $element['#description'] !== ''
    ? $element['#description']
    : $this->t('Leave blank to use the time of form submission.');
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
