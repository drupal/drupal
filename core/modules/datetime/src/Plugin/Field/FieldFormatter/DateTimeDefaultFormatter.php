<?php

/**
 * @file
 * Contains \Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeDefaultFormatter.
 */

namespace Drupal\datetime\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\Date;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'datetime_default' formatter.
 *
 * @FieldFormatter(
 *   id = "datetime_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class DateTimeDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'format_type' => 'medium',
    ) + parent::defaultSettings();
  }

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\Date
   */
  protected $dateService;

  /**
   * The date storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateStorage;

  /**
   * Constructs a new DateTimeDefaultFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Datetime\Date $date_service
   *   The date service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $date_storage
   *   The date storage.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, Date $date_service, EntityStorageInterface $date_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->dateService = $date_service;
    $this->dateStorage = $date_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('date'),
      $container->get('entity.manager')->getStorage('date_format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {

    $elements = array();

    foreach ($items as $delta => $item) {

      $formatted_date = '';
      $iso_date = '';

      if ($item->date) {
        $date = $item->date;
        // Create the ISO date in Universal Time.
        $iso_date = $date->format("Y-m-d\TH:i:s") . 'Z';

        // The formatted output will be in local time.
        $date->setTimeZone(timezone_open(drupal_get_user_timezone()));
        if ($this->getFieldSetting('datetime_type') == 'date') {
          // A date without time will pick up the current time, use the default.
          datetime_date_default_time($date);
        }
        $formatted_date = $this->dateFormat($date);
      }

      // Display the date using theme datetime.
      $elements[$delta] = array(
        '#theme' => 'datetime',
        '#text' => $formatted_date,
        '#html' => FALSE,
        '#attributes' => array(
          'datetime' => $iso_date,
        ),
      );
      if (!empty($item->_attributes)) {
        $elements[$delta]['#attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }

    return $elements;

  }

  /**
   * Creates a formatted date value as a string.
   *
   * @param object $date
   *   A date object.
   *
   * @return string
   *   A formatted date string using the chosen format.
   */
  function dateFormat($date) {
    $format_type = $this->getSetting('format_type');
    return $this->dateService->format($date->getTimestamp(), $format_type);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $time = new DrupalDateTime();
    $format_types = $this->dateStorage->loadMultiple();
    foreach ($format_types as $type => $type_info) {
      $format = $this->dateService->format($time->format('U'), $type);
      $options[$type] = $type_info->label() . ' (' . $format . ')';
    }

    $elements['format_type'] = array(
      '#type' => 'select',
      '#title' => t('Date format'),
      '#description' => t("Choose a format for displaying the date. Be sure to set a format appropriate for the field, i.e. omitting time for a field that only has a date."),
      '#options' => $options,
      '#default_value' => $this->getSetting('format_type'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $date = new DrupalDateTime();
    $summary[] = t('Format: @display', array('@display' => $this->dateFormat($date, FALSE)));
    return $summary;
  }

}
