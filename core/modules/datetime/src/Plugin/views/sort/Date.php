<?php

/**
 * @file
 * Contains \Drupal\datetime\Plugin\views\sort\Date.
 */

namespace Drupal\datetime\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\Date as NumericDate;

/**
 * Basic sort handler for datetime fields.
 *
 * This handler enables granularity, which is the ability to make dates
 * equivalent based upon nearness.
 *
 * @ViewsSort("datetime")
 */
class Date extends NumericDate {

  /**
   * Override to account for dates stored as strings.
   */
  public function getDateField() {
    // Return the real field, since it is already in string format.
    return "$this->tableAlias.$this->realField";
  }

  /**
   * Override query to provide 'second' granularity.
   */
  public function query() {
    $this->ensureMyTable();
    switch ($this->options['granularity']) {
      case 'second':
        $formula = $this->getDateFormat('YmdHis');
        $this->query->addOrderBy(NULL, $formula, $this->options['order'], $this->tableAlias . '_' . $this->field . '_' . $this->options['granularity']);
        return;
    }

    // All other granularities are handled by the numeric sort handler.
    parent::query();
  }

}
