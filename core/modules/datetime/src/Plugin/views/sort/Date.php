<?php

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
   * {@inheritdoc}
   *
   * Overridden in order to pass in the string date flag.
   */
  public function getDateFormat($format) {
    return $this->query->getDateFormat($this->getDateField(), $format, TRUE);
  }

}
