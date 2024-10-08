<?php

namespace Drupal\Core\Field\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Plugin implementation of the 'timestamp' formatter as time ago.
 */
#[FieldFormatter(
  id: 'timestamp_ago',
  label: new TranslatableMarkup('Time ago'),
  field_types: [
    'timestamp',
    'created',
    'changed',
  ],
)]
class TimestampAgoFormatter extends FormatterBase {

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
   * Constructs a TimestampAgoFormatter object.
   *
   * @param string $plugin_id
   *   The plugin ID for the formatter.
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
   *   Any third party settings.
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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // @see \Drupal\Core\Field\FormatterPluginManager::createInstance().
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
  public static function defaultSettings() {
    return [
      'future_format' => '@interval hence',
      'past_format' => '@interval ago',
      'granularity' => 2,
    ] + parent::defaultSettings();
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
      '#required' => TRUE,
      '#description' => $this->t('Use <em>@interval</em> where you want the formatted interval text to appear.'),
    ];

    $form['past_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Past format'),
      '#default_value' => $this->getSetting('past_format'),
      '#required' => TRUE,
      '#description' => $this->t('Use <em>@interval</em> where you want the formatted interval text to appear.'),
    ];

    $form['granularity'] = [
      '#type' => 'number',
      '#title' => $this->t('Granularity'),
      '#description' => $this->t('How many time interval units should be shown in the formatted output.'),
      '#default_value' => $this->getSetting('granularity') ?: 2,
      '#min' => 1,
      '#max' => 7,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $future_date = new DrupalDateTime('1 year 1 month 1 week 1 day 1 hour 1 minute 1 second');
    $past_date = new DrupalDateTime('-1 year -1 month -1 week -1 day -1 hour -1 minute -1 second');
    $granularity = $this->getSetting('granularity');
    $options = [
      'granularity' => $granularity,
      'return_as_object' => FALSE,
    ];

    $future_date_interval = new FormattableMarkup($this->getSetting('future_format'), ['@interval' => $this->dateFormatter->formatTimeDiffUntil($future_date->getTimestamp(), $options)]);
    $past_date_interval = new FormattableMarkup($this->getSetting('past_format'), ['@interval' => $this->dateFormatter->formatTimeDiffSince($past_date->getTimestamp(), $options)]);

    $summary[] = $this->t('Future date: %display', ['%display' => $future_date_interval]);
    $summary[] = $this->t('Past date: %display', ['%display' => $past_date_interval]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if ($item->value) {
        $updated = $this->formatTimestamp($item->value);
      }
      else {
        $updated = [
          '#markup' => $this->t('never'),
        ];
      }

      $elements[$delta] = $updated;
    }

    return $elements;
  }

  /**
   * Formats a timestamp.
   *
   * @param int $timestamp
   *   A UNIX timestamp to format.
   *
   * @return array
   *   The formatted timestamp string using the past or future format setting.
   */
  protected function formatTimestamp($timestamp) {
    $granularity = $this->getSetting('granularity');
    $options = [
      'granularity' => $granularity,
      'return_as_object' => TRUE,
    ];

    if ($this->request->server->get('REQUEST_TIME') > $timestamp) {
      $result = $this->dateFormatter->formatTimeDiffSince($timestamp, $options);
      $build = [
        '#markup' => new FormattableMarkup($this->getSetting('past_format'), ['@interval' => $result->getString()]),
      ];
    }
    else {
      $result = $this->dateFormatter->formatTimeDiffUntil($timestamp, $options);
      $build = [
        '#markup' => new FormattableMarkup($this->getSetting('future_format'), ['@interval' => $result->getString()]),
      ];
    }
    CacheableMetadata::createFromObject($result)->applyTo($build);
    return $build;
  }

}
