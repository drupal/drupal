<?php

/**
 * @file
 * Definition of Views\node\Plugin\views\argument\CreatedFullDate.
 */

namespace Views\node\Plugin\views\argument;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugin\views\argument\Date;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;

/**
 * Argument handler for a full date (CCYYMMDD)
 *
 * @Plugin(
 *   id = "node_created_fulldate",
 *   module = "node"
 * )
 */
class CreatedFullDate extends Date {

  /**
   * Constructs a CreatedFullDate object.
   */
  public function __construct(array $configuration, $plugin_id, DiscoveryInterface $discovery) {
    parent::__construct($configuration, $plugin_id, $discovery);

    $this->format = 'F j, Y';
    $this->arg_format = 'Ymd';
    $this->formula = views_date_sql_format($this->arg_format, "***table***.$this->realField");
  }

  /**
   * Provide a link to the next level of the view
   */
  function summary_name($data) {
    $created = $data->{$this->name_alias};
    return format_date(strtotime($created . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

  /**
   * Provide a link to the next level of the view
   */
  function title() {
    return format_date(strtotime($this->argument . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

}
