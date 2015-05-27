<?php

/**
 * @file
 * Contains \Drupal\datetime\Plugin\views\filter\String.
 */

namespace Drupal\datetime\Plugin\views\filter;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\filter\Date as NumericDate;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Date/time views filter.
 *
 * Even thought dates are stored as strings, the numeric filter is extended
 * because it provides more sensible operators.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("datetime")
 */
class Date extends NumericDate implements ContainerFactoryPluginInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new Date handler.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateFormatter $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dateFormatter = $date_formatter;
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
   * Date format for SQL conversion.
   *
   * @var string
   *
   * @see \Drupal\views\Plugin\views\query\Sql::getDateFormat()
   */
  protected static $dateFormat = 'Y-m-d H:i:s';

  /**
   * Override parent method, which deals with dates as integers.
   */
  protected function opBetween($field) {
    $origin = ($this->value['type'] == 'offset') ? REQUEST_TIME : 0;
    $a = intval(strtotime($this->value['min'], $origin));
    $b = intval(strtotime($this->value['max'], $origin));

    // Convert to ISO format and format for query.
    $a = $this->query->getDateFormat("'" . $this->dateFormatter->format($a, 'custom', 'c') . "'", static::$dateFormat, TRUE);
    $b = $this->query->getDateFormat("'" . $this->dateFormatter->format($b, 'custom', 'c') . "'", static::$dateFormat, TRUE);

    // This is safe because we are manually scrubbing the values.
    $operator = strtoupper($this->operator);
    $field = $this->query->getDateFormat($field, static::$dateFormat, TRUE);
    $this->query->addWhereExpression($this->options['group'], "$field $operator $a AND $b");
  }

  /**
   * Override parent method, which deals with dates as integers.
   */
  protected function opSimple($field) {
    $origin =  (!empty($this->value['type']) && $this->value['type'] == 'offset') ? REQUEST_TIME : 0;
    $value = intval(strtotime($this->value['value'], $origin));

    // Convert to ISO.
    $value = $this->query->getDateFormat("'" . $this->dateFormatter->format($value, 'custom', 'c') . "'", static::$dateFormat, TRUE);

    // This is safe because we are manually scrubbing the value.
    $field = $this->query->getDateFormat($field, static::$dateFormat, TRUE);
    $this->query->addWhereExpression($this->options['group'], "$field $this->operator $value");
  }

}
