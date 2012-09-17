<?php

/**
 * @file
 * Definition of Views\node\Plugin\views\argument\CreatedWeek.
 */

namespace Views\node\Plugin\views\argument;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugin\views\argument\Date;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;

/**
 * Argument handler for a week.
 *
 * @Plugin(
 *   id = "node_created_week",
 *   module = "node"
 * )
 */
class CreatedWeek extends Date {

  /**
   * Constructs a CreatedWeek object.
   */
  public function __construct(array $configuration, $plugin_id, DiscoveryInterface $discovery) {
    parent::__construct($configuration, $plugin_id, $discovery);

    $this->arg_format = 'w';
    $this->formula = views_date_sql_extract('WEEK', "***table***.$this->realField");
  }

  /**
   * Provide a link to the next level of the view
   */
  function summary_name($data) {
    $created = $data->{$this->name_alias};
    return t('Week @week', array('@week' => $created));
  }

}
