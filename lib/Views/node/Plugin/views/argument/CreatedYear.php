<?php

/**
 * @file
 * Definition of Views\node\Plugin\views\argument\CreatedYear.
 */

namespace Views\node\Plugin\views\argument;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugin\views\argument\Date;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;

/**
 * Argument handler for a year (CCYY)
 *
 * @Plugin(
 *   id = "node_created_year",
 *   module = "node"
 * )
 */
class CreatedYear extends Date {

  /**
   * Constructs a CreatedYear object.
   */
  public function __construct(array $configuration, $plugin_id, DiscoveryInterface $discovery) {
    parent::__construct($configuration, $plugin_id, $discovery);

    $this->arg_format = 'Y';
    $this->formula = views_date_sql_extract('YEAR', "***table***.$this->realField");
  }

}
