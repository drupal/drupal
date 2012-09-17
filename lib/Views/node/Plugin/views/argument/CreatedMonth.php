<?php

/**
 * @file
 * Definition of Views\node\Plugin\views\argument\CreatedMonth.
 */

namespace Views\node\Plugin\views\argument;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugin\views\argument\Date;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;

/**
 * Argument handler for a month (MM)
 *
 * @Plugin(
 *   id = "node_created_month",
 *   module = "node"
 * )
 */
class CreatedMonth extends Date {

  /**
   * Constructs a CreatedMonth object.
   */
  public function __construct(array $configuration, $plugin_id, DiscoveryInterface $discovery) {
    parent::__construct($configuration, $plugin_id, $discovery);

    $this->formula = views_date_sql_extract('MONTH', "***table***.$this->realField");
    $this->format = 'F';
    $this->arg_format = 'm';
  }

  /**
   * Provide a link to the next level of the view
   */
  function summary_name($data) {
    $month = str_pad($data->{$this->name_alias}, 2, '0', STR_PAD_LEFT);
    return format_date(strtotime("2005" . $month . "15" . " 00:00:00 UTC" ), 'custom', $this->format, 'UTC');
  }

  /**
   * Provide a link to the next level of the view
   */
  function title() {
    $month = str_pad($this->argument, 2, '0', STR_PAD_LEFT);
    return format_date(strtotime("2005" . $month . "15" . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

  function summary_argument($data) {
    // Make sure the argument contains leading zeroes.
    return str_pad($data->{$this->base_alias}, 2, '0', STR_PAD_LEFT);
  }

}
