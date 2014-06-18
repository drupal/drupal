<?php
/**
 * @file
 * Contains \Drupal\datetime\Plugin\Field\FieldWidget\DateTimeDefaultWidget.
 */

namespace Drupal\datetime\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;

/**
 * Plugin implementation of the 'datetime_default' widget.
 *
 * @FieldWidget(
 *   id = "datetime_default",
 *   label = @Translation("Date and time"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class DateTimeDefaultWidget extends WidgetBase {

  /**
   * The date format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    // @todo Inject this once https://drupal.org/node/2035317 is in.
    $this->dateStorage = \Drupal::entityManager()->getStorage('date_format');
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    // We are nesting some sub-elements inside the parent, so we need a wrapper.
    // We also need to add another #title attribute at the top level for ease in
    // identifying this item in error messages. We do not want to display this
    // title because the actual title display is handled at a higher level by
    // the Field module.

    $element['#theme_wrappers'][] = 'datetime_wrapper';
    $element['#attributes']['class'][] = 'container-inline';
    $element['#element_validate'][] = 'datetime_datetime_widget_validate';

    // Identify the type of date and time elements to use.
    switch ($this->getFieldSetting('datetime_type')) {
      case DateTimeItem::DATETIME_TYPE_DATE:
        $date_type = 'date';
        $time_type = 'none';
        $date_format = $this->dateStorage->load('html_date')->getPattern();
        $time_format = '';
        $element_format = $date_format;
        $storage_format = DATETIME_DATE_STORAGE_FORMAT;
        break;

      default:
        $date_type = 'date';
        $time_type = 'time';
        $date_format = $this->dateStorage->load('html_date')->getPattern();
        $time_format = $this->dateStorage->load('html_time')->getPattern();
        $element_format = $date_format . ' ' . $time_format;
        $storage_format = DATETIME_DATETIME_STORAGE_FORMAT;
        break;
    }

    $element['value'] = array(
      '#type' => 'datetime',
      '#default_value' => NULL,
      '#date_increment' => 1,
      '#date_date_format'=>  $date_format,
      '#date_date_element' => $date_type,
      '#date_date_callbacks' => array(),
      '#date_time_format' => $time_format,
      '#date_time_element' => $time_type,
      '#date_time_callbacks' => array(),
      '#date_timezone' => drupal_get_user_timezone(),
      '#required' => $element['#required'],
    );

    // Set the storage and widget options so the validation can use them. The
    // validator will not have access to the field definition.
    $element['value']['#date_element_format'] = $element_format;
    $element['value']['#date_storage_format'] = $storage_format;
    if ($items[$delta]->date) {
      $date = $items[$delta]->date;
      // The date was created and verified during field_load(), so it is safe to
      // use without further inspection.
      $date->setTimezone(new \DateTimeZone($element['value']['#date_timezone']));
      if ($this->getFieldSetting('datetime_type') == DateTimeItem::DATETIME_TYPE_DATE) {
        // A date without time will pick up the current time, use the default
        // time.
        datetime_date_default_time($date);
      }
      $element['value']['#default_value'] = $date;
    }

    return $element;
  }

}
