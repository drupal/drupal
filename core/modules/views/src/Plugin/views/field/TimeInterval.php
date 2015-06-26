<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\field\TimeInterval.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide proper displays for time intervals.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("time_interval")
 */
class TimeInterval extends FieldPluginBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a TimeInterval plugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateFormatter $date_formatter) {
    $this->dateFormatter = $date_formatter;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['granularity'] = array('default' => 2);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['granularity'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Granularity'),
      '#description' => $this->t('How many different units to display in the string.'),
      '#default_value' => $this->options['granularity'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $values->{$this->field_alias};
    return $this->dateFormatter->formatInterval($value, isset($this->options['granularity']) ? $this->options['granularity'] : 2);
  }

}
