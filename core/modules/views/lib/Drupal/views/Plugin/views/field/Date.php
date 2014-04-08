<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\field\Date.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\Date as DateService;

/**
 * A handler to provide proper displays for dates.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("date")
 */
class Date extends FieldPluginBase {

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\Date
   */
  protected $dateService;

  /**
   * The date format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateFormatStorage;

  /**
   * Constructs a new Date object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Datetime\Date $date_service
   *   The date service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $date_format_storage
   *   The date format storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateService $date_service, EntityStorageInterface $date_format_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->dateService = $date_service;
    $this->dateFormatStorage = $date_format_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('date'),
      $container->get('entity.manager')->getStorage('date_format')
    );
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['date_format'] = array('default' => 'small');
    $options['custom_date_format'] = array('default' => '');
    $options['timezone'] = array('default' => '');

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {

    $date_formats = array();
    foreach ($this->dateFormatStorage->loadMultiple() as $machine_name => $value) {
      $date_formats[$machine_name] = t('@name format: @date', array('@name' => $value->label(), '@date' => $this->dateService->format(REQUEST_TIME, $machine_name)));
    }

    $form['date_format'] = array(
      '#type' => 'select',
      '#title' => t('Date format'),
      '#options' => $date_formats + array(
        'custom' => t('Custom'),
        'raw time ago' => t('Time ago'),
        'time ago' => t('Time ago (with "ago" appended)'),
        'raw time hence' => t('Time hence'),
        'time hence' => t('Time hence (with "hence" appended)'),
        'raw time span' => t('Time span (future dates have "-" prepended)'),
        'inverse time span' => t('Time span (past dates have "-" prepended)'),
        'time span' => t('Time span (with "ago/hence" appended)'),
      ),
      '#default_value' => isset($this->options['date_format']) ? $this->options['date_format'] : 'small',
    );
    $form['custom_date_format'] = array(
      '#type' => 'textfield',
      '#title' => t('Custom date format'),
      '#description' => t('If "Custom", see <a href="http://us.php.net/manual/en/function.date.php" target="_blank">the PHP docs</a> for date formats. Otherwise, enter the number of different time units to display, which defaults to 2.'),
      '#default_value' => isset($this->options['custom_date_format']) ? $this->options['custom_date_format'] : '',
    );
    // Setup #states for all possible date_formats on the custom_date_format form element.
    foreach (array('custom', 'raw time ago', 'time ago', 'raw time hence', 'time hence', 'raw time span', 'time span', 'raw time span', 'inverse time span', 'time span') as $custom_date_possible) {
      $form['custom_date_format']['#states']['visible'][] = array(
        ':input[name="options[date_format]"]' => array('value' => $custom_date_possible),
      );
    }
    $form['timezone'] = array(
      '#type' => 'select',
      '#title' => t('Timezone'),
      '#description' => t('Timezone to be used for date output.'),
      '#options' => array('' => t('- Default site/user timezone -')) + system_time_zones(FALSE),
      '#default_value' => $this->options['timezone'],
    );
    foreach (array_merge(array('custom'), array_keys($date_formats)) as $timezone_date_formats) {
      $form['timezone']['#states']['visible'][] = array(
        ':input[name="options[date_format]"]' => array('value' => $timezone_date_formats),
      );
    }

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    $format = $this->options['date_format'];
    if (in_array($format, array('custom', 'raw time ago', 'time ago', 'raw time hence', 'time hence', 'raw time span', 'time span', 'raw time span', 'inverse time span', 'time span'))) {
      $custom_format = $this->options['custom_date_format'];
    }

    if ($value) {
      $timezone = !empty($this->options['timezone']) ? $this->options['timezone'] : NULL;
      $time_diff = REQUEST_TIME - $value; // will be positive for a datetime in the past (ago), and negative for a datetime in the future (hence)
      switch ($format) {
        case 'raw time ago':
          return format_interval($time_diff, is_numeric($custom_format) ? $custom_format : 2);
        case 'time ago':
          return t('%time ago', array('%time' => format_interval($time_diff, is_numeric($custom_format) ? $custom_format : 2)));
        case 'raw time hence':
          return format_interval(-$time_diff, is_numeric($custom_format) ? $custom_format : 2);
        case 'time hence':
          return t('%time hence', array('%time' => format_interval(-$time_diff, is_numeric($custom_format) ? $custom_format : 2)));
        case 'raw time span':
          return ($time_diff < 0 ? '-' : '') . format_interval(abs($time_diff), is_numeric($custom_format) ? $custom_format : 2);
        case 'inverse time span':
          return ($time_diff > 0 ? '-' : '') . format_interval(abs($time_diff), is_numeric($custom_format) ? $custom_format : 2);
        case 'time span':
          return t(($time_diff < 0 ? '%time hence' : '%time ago'), array('%time' => format_interval(abs($time_diff), is_numeric($custom_format) ? $custom_format : 2)));
        case 'custom':
          if ($custom_format == 'r') {
            return format_date($value, $format, $custom_format, $timezone, 'en');
          }
          return format_date($value, $format, $custom_format, $timezone);
        default:
          return format_date($value, $format, '', $timezone);
      }
    }
  }

}
