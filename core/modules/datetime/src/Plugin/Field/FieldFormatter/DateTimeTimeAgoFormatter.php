<?php

namespace Drupal\datetime\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Time ago' formatter for 'datetime' fields.
 *
 * @FieldFormatter(
 *   id = "datetime_time_ago",
 *   label = @Translation("Time ago"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class DateTimeTimeAgoFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The current Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a DateTimeTimeAgoFormatter object.
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
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, DateFormatterInterface $date_formatter, Request $request) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->dateFormatter = $date_formatter;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [
      'future_format' => '@interval hence',
      'past_format' => '@interval ago',
      'granularity' => 2,
    ] + parent::defaultSettings();

    return $settings;
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
      $container->get('date.formatter'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $date = $item->date;
      $output = [];
      if (!empty($item->date)) {
        if ($this->getFieldSetting('datetime_type') == 'date') {
          // A date without time will pick up the current time, use the default.
          datetime_date_default_time($date);
        }
        $output = $this->formatDate($date);
      }
      $elements[$delta] = $output;
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['future_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Future format'),
      '#default_value' => $this->getSetting('future_format'),
      '#description' => $this->t('Use <em>@interval</em> where you want the formatted interval text to appear.'),
    ];

    $form['past_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Past format'),
      '#default_value' => $this->getSetting('past_format'),
      '#description' => $this->t('Use <em>@interval</em> where you want the formatted interval text to appear.'),
    ];

    $form['granularity'] = [
      '#type' => 'number',
      '#title' => $this->t('Granularity'),
      '#default_value' => $this->getSetting('granularity'),
      '#description' => $this->t('How many time units should be shown in the formatted output.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $future_date = new DrupalDateTime('1 year 1 month 1 week 1 day 1 hour 1 minute');
    $past_date = new DrupalDateTime('-1 year -1 month -1 week -1 day -1 hour -1 minute');
    $summary[] = t('Future date: %display', ['%display' => $this->formatDate($future_date)]);
    $summary[] = t('Past date: %display', ['%display' => $this->formatDate($past_date)]);

    return $summary;
  }

  /**
   * Formats a date/time as a time interval.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime|object $date
   *   A date/time object.
   *
   * @return array
   *   The formatted date/time string using the past or future format setting.
   */
  protected function formatDate(DrupalDateTime $date) {
    $granularity = $this->getSetting('granularity');
    $timestamp = $date->getTimestamp();
    $options = [
      'granularity' => $granularity,
      'return_as_object' => TRUE,
    ];

    if ($this->request->server->get('REQUEST_TIME') > $timestamp) {
      $result = $this->dateFormatter->formatTimeDiffSince($timestamp, $options);
      $build = [
        '#markup' => SafeMarkup::format($this->getSetting('past_format'), ['@interval' => $result->getString()]),
      ];
    }
    else {
      $result = $this->dateFormatter->formatTimeDiffUntil($timestamp, $options);
      $build = [
        '#markup' => SafeMarkup::format($this->getSetting('future_format'), ['@interval' => $result->getString()]),
      ];
    }
    CacheableMetadata::createFromObject($result)->applyTo($build);
    return $build;
  }

}
