<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldFormatter\TimestampFormatter.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'timestamp' formatter.
 *
 * @FieldFormatter(
 *   id = "timestamp",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "timestamp",
 *     "created",
 *     "changed",
 *   }
 * )
 */
class TimestampFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The date format entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateFormatStorage;

  /**
   * Constructs a new TimestampFormatter.
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
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $date_format_storage
   *   The date format storage.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, DateFormatter $date_formatter, EntityStorageInterface $date_format_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->dateFormatter = $date_formatter;
    $this->dateFormatStorage = $date_format_storage;
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
      $container->get('entity.manager')->getStorage('date_format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'date_format' => 'medium',
      'custom_date_format' => '',
      'timezone' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $date_formats = array();

    foreach ($this->dateFormatStorage->loadMultiple() as $machine_name => $value) {
      $date_formats[$machine_name] = $this->t('@name format: @date', array('@name' => $value->label(), '@date' => $this->dateFormatter->format(REQUEST_TIME, $machine_name)));
    }

    $date_formats['custom'] = $this->t('Custom');

    $elements['date_format'] = array(
      '#type' => 'select',
      '#title' => $this->t('Date format'),
      '#options' => $date_formats,
      '#default_value' => $this->getSetting('date_format') ?: 'medium',
    );

    $elements['custom_date_format'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Custom date format'),
      '#description' => $this->t('See <a href="@url" target="_blank">the documentation for PHP date formats</a>.', ['@url' => 'http://php.net/manual/function.date.php']),
      '#default_value' => $this->getSetting('custom_date_format') ?: '',
    );

    $elements['custom_date_format']['#states']['visible'][] = array(
      ':input[name="options[settings][date_format]"]' => array('value' => 'custom'),
    );

    $elements['timezone'] = array(
      '#type' => 'select',
      '#title' => $this->t('Time zone'),
      '#options' => array('' => $this->t('- Default site/user time zone -')) + system_time_zones(FALSE),
      '#default_value' => $this->getSetting('timezone'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $date_format = $this->getSetting('date_format');
    $summary[] = $this->t('Date format: @date_format', array('@date_format' => $date_format));
    if ($this->getSetting('date_format') === 'custom' && ($custom_date_format = $this->getSetting('custom_date_format'))) {
      $summary[] = $this->t('Custom date format: @custom_date_format', array('@custom_date_format' => $custom_date_format));
    }
    if ($timezone = $this->getSetting('timezone')) {
      $summary[] = $this->t('Time zone: @timezone', array('@timezone' => $timezone));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    $date_format = $this->getSetting('date_format');
    $custom_date_format = '';
    $timezone = $this->getSetting('timezone') ?: NULL;
    $langcode = NULL;

    // If an RFC2822 date format is requested, then the month and day have to
    // be in English. @see http://www.faqs.org/rfcs/rfc2822.html
    if ($date_format === 'custom' && ($custom_date_format = $this->getSetting('custom_date_format')) === 'r') {
      $langcode = 'en';
    }

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#cache' => [
          'contexts' => [
            'timezone',
          ],
        ],
        '#markup' => $this->dateFormatter->format($item->value, $date_format, $custom_date_format, $timezone, $langcode),
      ];
    }

    return $elements;
  }

}
