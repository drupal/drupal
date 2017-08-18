<?php

namespace Drupal\dblog\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Exposes log types to views module.
 *
 * @ViewsFilter("dblog_types")
 */
class DblogTypes extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $this->valueOptions = _dblog_get_message_types();
    }
    return $this->valueOptions;
  }

}
