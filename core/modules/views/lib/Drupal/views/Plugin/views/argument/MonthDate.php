<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\argument\MonthDate.
 */

namespace Drupal\views\Plugin\views\argument;

/**
 * Argument handler for a month (MM)
 *
 * @ViewsArgument("date_month")
 */
class MonthDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $format = 'F';

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'm';

  /**
   * Provide a link to the next level of the view
   */
  public function summaryName($data) {
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

  public function summaryArgument($data) {
    // Make sure the argument contains leading zeroes.
    return str_pad($data->{$this->base_alias}, 2, '0', STR_PAD_LEFT);
  }

}
