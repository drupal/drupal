<?php

namespace Drupal\datetime\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Date as NumericDate;

/**
 * Abstract argument handler for dates.
 *
 * Adds an option to set a default argument based on the current date.
 *
 * Definitions terms:
 * - many to one: If true, the "many to one" helper will be used.
 * - invalid input: A string to give to the user for obviously invalid input.
 *                  This is deprecated in favor of argument validators.
 *
 * @see \Drupal\views\ManyTonOneHelper
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("datetime")
 */
class Date extends NumericDate {

  /**
   * {@inheritdoc}
   */
  public function getDateField() {
    // Return the real field, since it is already in string format.
    return "$this->tableAlias.$this->realField";
  }

  /**
   * {@inheritdoc}
   */
  public function getDateFormat($format) {
    // Pass in the string-field option.
    return $this->query->getDateFormat($this->getDateField(), $format, TRUE);
  }

}
